document.addEventListener("DOMContentLoaded", function () {

    const btnMulai = document.getElementById("btnMulaiDeteksi");
    const form = document.querySelector("#modalDeteksi form");
    const video = document.getElementById("previewKamera");

    let stream = null;
    let isRunning = false;
    let sessionId = null;

    let canvas = null;
    let ctx = null;

    let smoothMap = {};
    let lastUpdate = 0;

    const API = "/api";

    // =========================
    // LOAD MODEL
    // =========================
    async function loadModels() {
        await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
        await faceapi.nets.faceExpressionNet.loadFromUri('/models');
        console.log("Model loaded");
    }

    loadModels();

    // =========================
    // EMA SMOOTH
    // =========================
    function ema(id, value) {
        if (smoothMap[id] === undefined) {
            smoothMap[id] = value;
        }
        smoothMap[id] = (smoothMap[id] * 0.8) + (value * 0.2);
        return smoothMap[id];
    }

    // =========================
    // START / STOP
    // =========================
    btnMulai?.addEventListener("click", async function (e) {
        e.preventDefault();

        if (isRunning) {
            await stopDetection();
            return;
        }

        try {
            // CAMERA START
            stream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false
            });

            video.srcObject = stream;
            await video.play();

            // SESSION START
            const res = await fetch(`${API}/session/start`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    nama_kelas: form?.querySelector("[name=kelas]")?.value,
                    dosen: form?.querySelector("[name=user_id]")?.value,
                    total_mahasiswa: 100
                })
            });

            const data = await res.json();

            if (!res.ok || !data.session_id) {
                throw new Error("Gagal start session");
            }

            sessionId = data.session_id;
            isRunning = true;

            setRunning();
            startDetectLoop();

        } catch (err) {
            console.error(err);
            alert(err.message);
        }
    });

    // =========================
    // STOP
    // =========================
    async function stopDetection() {

        isRunning = false;

        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }

        if (canvas) canvas.remove();

        try {
            await fetch(`${API}/session/stop`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    session_id: sessionId
                })
            });
        } catch (e) {}

        sessionId = null;
        setIdle();
    }

    // =========================
    // DETECTION ONLY (NO STORE)
    // =========================
    function startDetectLoop() {

        const old = document.getElementById("overlayCanvas");
        if (old) old.remove();

        canvas = document.createElement("canvas");
        canvas.id = "overlayCanvas";
        ctx = canvas.getContext("2d");

        video.parentNode.style.position = "relative";

        canvas.style.position = "absolute";
        canvas.style.top = "0";
        canvas.style.left = "0";
        canvas.style.zIndex = "10";
        canvas.style.pointerEvents = "none";

        video.parentNode.appendChild(canvas);

        const displaySize = {
            width: video.clientWidth,
            height: video.clientHeight
        };

        faceapi.matchDimensions(canvas, displaySize);

        async function loop() {

            if (!isRunning) return;

            requestAnimationFrame(loop);

            const now = Date.now();
            if (now - lastUpdate < 500) return;
            lastUpdate = now;

            if (video.readyState < 2) return;

            canvas.width = displaySize.width;
            canvas.height = displaySize.height;

            const detections = await faceapi
                .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({
                    inputSize: 224,
                    scoreThreshold: 0.5
                }))
                .withFaceExpressions();

            const resized = faceapi.resizeResults(detections, displaySize);

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            resized.forEach((res, i) => {

                const box = res.detection.box;
                const exp = res.expressions;

                const happy = exp.happy || 0;
                const angry = exp.angry || 0;

                let raw = happy;
                if (angry > happy) raw = 1 - angry;

                const score = ema(i, raw);
                const percent = Math.round(score * 100);

                const isPositive = score >= 0.68;

                const label = isPositive ? "POSITIF" : "NEGATIF";
                const color = isPositive ? "lime" : "red";

                new faceapi.draw.DrawBox(box, {
                    label: `Mhs ${i + 1} ${label} (${percent}%)`,
                    boxColor: color
                }).draw(canvas);
            });
        }

        loop();
    }

    // =========================
    // UI
    // =========================
    function setRunning() {
        btnMulai.innerHTML = "Stop Deteksi";
        btnMulai.style.background = "#dc3545";
        btnMulai.style.color = "#fff";
    }

    function setIdle() {
        btnMulai.innerHTML = "Mulai Deteksi";
        btnMulai.style.background = "#28a745";
        btnMulai.style.color = "#fff";
    }

});