let x = 320;
let y = 260;

var socket;

var message = "Waiting for message...";

var chunks = [];

var scl = 1;

let mapBufferImage;

let drawOverlay = true;

let player = null;

function setup() {
    var cnv = createCanvas(640, 520);
    
    // Create image buffer, this should be huge performance improvement
    // Currently we're drawing 1600 chunks at about 0.3 Frames per second
    mapBufferImage = createGraphics(width, height);

    var x = (windowWidth - width) / 2;
    var y = (windowHeight - height) / 2;
    cnv.position(x, y);

    // Create WebSocket connection.
    socket = new WebSocket('ws://localhost:27095');

    // Connection opened
    socket.addEventListener('open', function (event) {
        socket.send(JSON.stringify({ 'type': 'subscribe' }));

        console.log('connected to pocketcore');
    });

    // Listen for messages
    socket.addEventListener('message', function (event) {
        let response = JSON.parse(event.data);
        console.log(response);

        switch(response.type) {
            case 'message':
                console.log('Got message: ' + response.body.message);
                message = response.body.message;
                break;
            case 'chunk':
                recieveChunk(response.body.chunk);
                console.log('Recieved chunkX: ' + response.body.chunk.x + ', chunkZ: ' + response.body.chunk.z);
                break;

            case 'chunks':
                recieveChunks(response.body);
                console.log('Recieved chunks in bulk, size: ' + floor(response.body.length) + ' bytes');
                break;

            case 'player.join':
                console.log('Player ' + response.body.name + ' has joined');
                player = {
                    name: response.body.name,
                    eid: response.body.eid
                }
                break;
            
            case 'player.leave':
                console.log('Player ' + response.body.name + ' has left. Reason: ' . response.body.reason);
                player = null;
                break;

            case 'entity.position':
                if(!player) {
                    player = {
                        name: 'unknown',
                        position: {x: 0, y: 0, z: 0}
                    }
                }
                player.position = response.body.location;
                break;

            default:
                console.error('unhandled response: ' + response.type);
                break;
        }
    });

    noStroke();
    // frameRate(2);
}

function draw() {
    background(51);

    // Draw Map buffer
    image(mapBufferImage, 0, 0, width, height);

    // Render grid overlay
    if(drawOverlay) {
        gridOverlay();
        mouseCoordinates();
    }

    if(player) {
        if(player.position) {
            // Render player
            fill('red');
            rectMode(CENTER);
            rect(player.position.x, player.position.z, 6, 6);
        }
    }

    // Message rendering 
    // fill('#FFF');
    // textSize(18);
    // textAlign(CENTER);
    // text(message, width / 2, height / 2);
}

function mouseCoordinates() {
    noStroke();
    fill('#FFF');
    textSize(12);
    let coord = canvasToWorld(mouseX, mouseY);
    let txt = `[${coord[0]}, ${coord[1]}]`;
    text(txt, mouseX + (txt.length * 12 / 5), mouseY);
}

function canvasToWorld(x, y) {
    return [
        x * scl,
        y * scl
    ];
}

function keyPressed() {
    if(keyCode === 32) drawOverlay = !drawOverlay;
}

function gridOverlay() {
    var chunkSize = 16 * scl;
    var xSize = floor(width / 16)
    var zSize = floor(height / 16);

    noFill();
    stroke('#000');

    for(x = 0; x < xSize; x++) {
        for(z = 0; z < zSize; z++) {
            rect(x * chunkSize, z * chunkSize, chunkSize, chunkSize);
        }
    }
}

function renderChunk(chunk) {
    let blockId;
    let chunkX = chunk.x;
    let chunkZ = chunk.z;
    let layer = chunk.layer;

    mapBufferImage.noStroke();
    for (var x = 0; x < 16; x++) {
        for (var z = 0; z < 16; z++) {
            blockId = layer[x][z];

            mapBufferImage.fill(getBlockColor(blockId));

            mapBufferImage.rect(
                (chunkX * 16 * scl) + (x * scl),
                (chunkZ * 16 * scl) + (z * scl),
                scl, scl
            );
        }
    }
}

function recieveChunk(chunk) {
    // Update, if chunk in position
    chunks[chunk.x + ':' + chunk.z] = chunk;

    // Edit mapBufferImage buffer with this new chunk
    renderChunk(chunk);
}

var blockColorMap = {
    // Grass
    '2': '#00b894',
    // Snow
    '78': '#dfe6e9',
    // Stone
    '1': '#636e72',
    // Some plants
    '31': '#78e08f',
    // Oak leaves
    '18': '#009432',
    // Water
    '9': '#0652DD',
    // Sand
    '12': '#ffeaa7',
    // Dead bush
    '31': '#cc8e35',
    // Dirt
    '3': '#f0932b'
}

function getBlockColor(blockId) {
    return blockColorMap[blockId] ?? ((id) => {
        
        console.log('unknown block ' + id);
        return 'red';

    })(blockId);
}

function recieveChunks(chunksBase64) {
    let chunks = JSON.parse(atob(chunksBase64));

    console.log(chunks);

    for(let x = chunks.length - 1; x >= 0; x--) {
        for(let z = chunks[x].length - 1; z >= 0; z--) {
            let chunk = chunks[x][z];

            recieveChunk(chunk);
        }
    }
}