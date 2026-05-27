<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Deteksi Emosi</title>

    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

    <style>
        body{
            margin:0;
            background:#111;
            display:flex;
            justify-content:center;
            align-items:center;
            height:100vh;
        }

        .container{
            position:relative;
            width:720px;
            height:560px;
        }

        video, canvas{
            position:absolute;
            top:0;
            left:0;
            width:720px;
            height:560px;
            border-radius:12px;
        }

        video{
            border:3px solid white;
            object-fit:cover;
        }

        .status{
            position:absolute;
            top:10px;
            left:10px;
            z-index:10;
            background:#000000cc;
            color:white;
            padding:8px 12px;
            border-radius:8px;
            font-size:14px;
        }
    </style>
</head>
<body>

<div class="container">

    <div class="status" id="status">Loading...</div>

    <video id="video" autoplay muted playsinline></video>

</div>

<script>

const video = document.getElementById('video');
const statusText = document.getElementById('status');

async function start() {

    await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
        faceapi.nets.faceExpressionNet.loadFromUri('/models')
    ]);

    statusText.innerHTML = 'Menyalakan kamera...';

    const stream = await navigator.mediaDevices.getUserMedia({
        video: true,
        audio: false
    });

    video.srcObject = stream;
}

start();

video.addEventListener('play', () => {

    statusText.innerHTML = 'Deteksi aktif';

    const canvas = faceapi.createCanvasFromMedia(video);
    document.querySelector('.container').append(canvas);

    const size = { width: 720, height: 560 };
    faceapi.matchDimensions(canvas, size);

    setInterval(async () => {

        const detections = await faceapi
            .detectAllFaces(
                video,
                new faceapi.TinyFaceDetectorOptions({
                    inputSize: 416,
                    scoreThreshold: 0.3
                })
            )
            .withFaceExpressions();

        const resized = faceapi.resizeResults(detections, size);

        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        resized.forEach(res => {

            const box = res.detection.box;
            const exp = res.expressions;

            let label = '';
            let color = '';

            // POSITIF = SENANG
            if (exp.happy > 0.6) {
                label = 'POSITIF 😊';
                color = 'rgb(50,205,50)';
            }

            // NEGATIF = MARAH
            else if (exp.angry > 0.5) {
                label = 'NEGATIF 😠';
                color = 'rgb(255,0,0)';
            }

            // kalau bukan 2 itu → skip (tidak ditampilkan)
            else {
                return;
            }

            new faceapi.draw.DrawBox(box, {
                label: label,
                boxColor: color
            }).draw(canvas);

        });

    }, 100);

});

</script>

</body>
</html>