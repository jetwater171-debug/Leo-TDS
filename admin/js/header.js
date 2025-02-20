
function setupMatrixRain() {
    const canvas = document.getElementById('matrix-rain');
    const ctx = canvas.getContext('2d');

    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    const characters = "01";
    const fontSize = 14;
    const columns = canvas.width / fontSize;
    const drops = [];

    for (let x = 0; x < columns; x++) {
        drops[x] = Math.random() * -100;
    }

    function draw() {
        ctx.fillStyle = 'rgba(27, 42, 71, 0.05)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        ctx.fillStyle = '#0F0';
        ctx.font = fontSize + 'px monospace';

        for (let i = 0; i < drops.length; i++) {
            const text = characters.charAt(Math.floor(Math.random() * characters.length));
            ctx.fillText(text, i * fontSize, drops[i] * fontSize);

            if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                drops[i] = 0;
            }
            drops[i]++;
        }
    }

    let interval = setInterval(draw, 35);
    return () => clearInterval(interval);
}

function typeText(text, element) {
    let index = 0;
    let isTyping = true;
    element.textContent = '';
    let animationRunning = true;
    
    function animate() {
        if (!animationRunning) return;
        
        if (isTyping) {
            if (index < text.length) {
                element.textContent += text.charAt(index);
                index++;
                setTimeout(animate, 100);
            } else {
                setTimeout(() => {
                    if (!animationRunning) return;
                    isTyping = false;
                    animate();
                }, 1000);
            }
        } else {
            if (index > 0) {
                element.textContent = text.substring(0, index - 1);
                index--;
                setTimeout(animate, 50);
            } else {
                setTimeout(() => {
                    if (!animationRunning) return;
                    isTyping = true;
                    animate();
                }, 500);
            }
        }
    }
    
    animate();
    
    // Return a cleanup function
    return () => {
        animationRunning = false;
        element.textContent = '';
    };
}

document.addEventListener('DOMContentLoaded', function() {

    flatpickr("#litepicker", {
        dateFomat: "DD.MM.YY",
        mode: "range",
        onClose: function(selectedDates, dateStr, instance) {
            if (selectedDates.length < 2) return;
            update_datepicker_dates(selectedDates);
        }
    });
    
    const updateBasesLink = document.getElementById('updateBases');
    const loadingAnimation = document.getElementById('loadingAnimation');
    const updateOverlay = document.getElementById('updateOverlay');
    const typingText = document.getElementById('typing-text');
    let typingCleanup = null;

    updateBasesLink.addEventListener('click', async function(e) {
        e.preventDefault();
        loadingAnimation.style.display = 'inline';
        updateOverlay.style.display = 'flex';
        setupMatrixRain();
        typingCleanup = typeText('GEOIP UPDATING...', typingText);

        try {
            const response = await fetch('../bases/update.php');
            const jsr = await response.json();
            if (!jsr.error) {
                alert('Update SUCCESSFULL: ' + jsr.result);
                location.reload();
            } else {
                alert('Error updating geobases: ' + jsr.result);
            }
        } catch (error) {
            alert('Error updating geobases: ' + error);
        } finally {
            if (typingCleanup) typingCleanup();
            loadingAnimation.style.display = 'none';
            updateOverlay.style.display = 'none';
        }
    });
});

async function sendAutoupdateRequest(action) {
    const response = await fetch('autoupdate.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=${action}`
    });
    return await response.json();
}

async function checkForUpdates() {
    const updateOverlay = document.getElementById('updateOverlay');
    const typingText = document.getElementById('typing-text');
    let typingCleanup = null;

    updateOverlay.style.display = 'flex';
    setupMatrixRain();
    typingCleanup = typeText('SYSTEM UPDATING...', typingText);

    try {
        const result = await sendAutoupdateRequest('check');
        
        if (!result.success) {
            alert('Error checking for updates: ' + result.message);
            return;
        }
        
        if (!result.hasUpdate) {
            alert('Your system is up to date!');
            return;
        }
        
        if (confirm(`An update to version ${result.version} is available. Would you like to update now?`)) {
            const updateResult = await sendAutoupdateRequest('update');
            
            if (updateResult.success) {
                alert('Update successful! The page will now reload.');
                location.reload();
            } else {
                alert('Error updating system: ' + updateResult.error);
            }
        }
    } catch (error) {
        alert('Error updating system: ' + error);
    } finally {
        if (typingCleanup) typingCleanup();
        updateOverlay.style.display = 'none';
    }
}


function update_datepicker_dates(selectedDates) {
    function formatDate(date) {
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = String(date.getFullYear()).slice(-2);
        return `${day}.${month}.${year}`;
    }
    let searchParams = new URLSearchParams(window.location.search);
    let d1 = formatDate(selectedDates[0]);
    let d2 = formatDate(selectedDates[1]);
    searchParams.set('startdate', d1);
    searchParams.set('enddate', d2);
    window.location.search = searchParams.toString();
}