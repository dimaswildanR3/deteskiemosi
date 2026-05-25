document.addEventListener("DOMContentLoaded", function () {

    const btnDashboard = document.getElementById("btnDashboardDeteksi");
    const btnMulai = document.getElementById("btnMulaiDeteksi");
    const form = document.querySelector("#modalDeteksi form");
    const video = document.getElementById("previewKamera");

    let cameraStream = null;
    let isDetecting = false;
    let sessionId = null;
    let intervalLoop = null;
    let studentCounter = 1;

    const API = "/api";




    btnMulai?.addEventListener("click", async function (e) {
        e.preventDefault();

        try {

            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false
            });

            if (video) {
                video.srcObject = cameraStream;
                await video.play();
            }

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

            $("#modalDeteksi").modal("hide");

            setRunning();
            startStoreLoop();

            console.log("SESSION STARTED:", sessionId);

        } catch (err) {
            console.error("START ERROR:", err);
            alert(err.message);
        }
    });




    function startStoreLoop() {

        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");

        intervalLoop = setInterval(async () => {

            if (!isDetecting || !video || !video.videoWidth) return;

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const blob = await new Promise(resolve => {
                canvas.toBlob(resolve, "image/jpeg", 0.8);
            });

            const label = Math.random() > 0.5 ? "POSITIF" : "NEGATIF";
            const confidence = (Math.random() * 0.4 + 0.6).toFixed(2);

            const formData = new FormData();
            formData.append("session_id", sessionId);
            formData.append("nomor_mahasiswa", studentCounter);
            formData.append("label", label);
            formData.append("confidence", confidence);
            formData.append("image", blob, "capture.jpg");

            fetch(`${API}/store`, {
                method: "POST",
                body: formData,
                headers: {
                    "Accept": "application/json"
                }
            })
            .then(res => res.text())
            .then(text => {
                try {
                    console.log("STORE OK:", JSON.parse(text));
                } catch {
                    console.warn("STORE NON-JSON:", text);
                }
            })
            .catch(err => console.error("STORE ERROR:", err));

            studentCounter++;

        }, 2000);
    }




    btnDashboard?.addEventListener("click", async function (e) {

        if (!isDetecting) return;

        e.preventDefault();

        try {

            isDetecting = false;

            if (cameraStream) {
                cameraStream.getTracks().forEach(t => t.stop());
                cameraStream = null;
            }

            if (intervalLoop) {
                clearInterval(intervalLoop);
                intervalLoop = null;
            }

            const res = await fetch(`${API}/session/stop`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    session_id: sessionId
                })
            });

            const text = await res.text();

            try {
                console.log("SESSION STOP:", JSON.parse(text));
            } catch {
                console.warn("STOP NON-JSON:", text);
            }

            setIdle();

        } catch (err) {
            console.error("STOP ERROR:", err);
        }
    });




    function setRunning() {
        if (!btnDashboard) return;

        btnDashboard.innerHTML = `<i class="fas fa-stop-circle"></i> Stop Deteksi`;
        btnDashboard.classList.remove("btn-success");
        btnDashboard.classList.add("btn-danger");
    }

    function setIdle() {
        if (!btnDashboard) return;

        btnDashboard.innerHTML = `<i class="fas fa-camera"></i> Deteksi Kamera`;
        btnDashboard.classList.remove("btn-danger");
        btnDashboard.classList.add("btn-success");
    }

});