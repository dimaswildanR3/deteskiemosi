document.addEventListener("DOMContentLoaded", function () {

    const btnMulai = document.getElementById("btnMulaiDeteksi");
    const form = document.querySelector("#modalDeteksi form");
    const video = document.getElementById("previewKamera");

    let cameraStream = null;
    let isDetecting = false;
    let sessionId = null;
    let intervalLoop = null;
    let studentCounter = 1;

    const API = "/api";

    // =========================
    // TOGGLE START / STOP
    // =========================
    btnMulai?.addEventListener("click", async function (e) {
        e.preventDefault();

        if (isDetecting) {
            await stopDetection();
            return;
        }

        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false
            });

            video.srcObject = cameraStream;
            await video.play();

            const res = await fetch(`${API}/session/start`, {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json"
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
            isDetecting = true;

            setRunning();
            startStoreLoop();

        } catch (err) {
            console.error(err);
            alert(err.message);
        }
    });

    // =========================
    // STOP DETECTION
    // =========================
    async function stopDetection() {

        if (!isDetecting) return;

        isDetecting = false;

        // stop camera
        if (cameraStream) {
            cameraStream.getTracks().forEach(t => t.stop());
            cameraStream = null;
        }

        // stop loop
        if (intervalLoop) {
            clearInterval(intervalLoop);
            intervalLoop = null;
        }

        // stop API
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

        // close modal
        $("#modalDeteksi").modal("hide");

        setIdle();
    }

    // =========================
    // CAPTURE LOOP
    // =========================
    function startStoreLoop() {

        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");

        intervalLoop = setInterval(async () => {

            if (!isDetecting || !video.videoWidth) return;

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            ctx.drawImage(video, 0, 0);

            const blob = await new Promise(resolve =>
                canvas.toBlob(resolve, "image/jpeg", 0.8)
            );

            const formData = new FormData();
            formData.append("session_id", sessionId);
            formData.append("nomor_mahasiswa", studentCounter++);
            formData.append("label", Math.random() > 0.5 ? "POSITIF" : "NEGATIF");
            formData.append("confidence", (Math.random() * 0.4 + 0.6).toFixed(2));
            formData.append("image", blob, "capture.jpg");

            fetch(`${API}/store`, {
                method: "POST",
                body: formData,
                headers: { "Accept": "application/json" }
            });

        }, 2000);
    }

    // =========================
    // UI STATE (FIX WARNA 100%)
    // =========================

    function setRunning() {
        if (!btnMulai) return;
    
        btnMulai.innerHTML = `<i class="fas fa-stop-circle"></i> Stop Deteksi`;
    
        btnMulai.style.background = "#dc3545"; // merah bootstrap
        btnMulai.style.border = "none";
        btnMulai.style.color = "#fff";
    
        btnMulai.className = "btn shadow";
    }
    
    function setIdle() {
        if (!btnMulai) return;
    
        btnMulai.innerHTML = `<i class="fas fa-play-circle"></i> Mulai Deteksi`;
    
        btnMulai.style.background = "#28a745"; // hijau bootstrap
        btnMulai.style.border = "none";
        btnMulai.style.color = "#fff";
    
        btnMulai.className = "btn shadow";
    }

});