let x = 320;
let y = 260;

var socket;

function setup() {
    var cnv = createCanvas(640, 520);
    var x = (windowWidth - width) / 2;
    var y = (windowHeight - height) / 2;
    cnv.position(x, y);

    socket = io()
    
    socket.on('connect', function () {
        socket.send('hi');

        socket.on('message', function (msg) {
            console.log(msg);
            document.getElementById('mybox').style.left = msg.left + 'px';
            document.getElementById('mybox').style.top = msg.top + 'px';
        });
        
    });


}

function draw() {
    background(51);

    x += random(-1, 1);
    y += random(-1, 1);

    stroke('#FF00FF');
    strokeWeight(5);
    ellipse(x, y, 1, 1);
}