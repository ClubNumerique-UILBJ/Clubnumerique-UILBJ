
    initHeartParticles();
    const subtitle = document.querySelector('.typing-animation');
    const text = "Une page souvenir qui parle de toi, de moi, de nous. Nos début notre, notre avenir, nos moments.";
    let index = 0;
    function typeWriter() {
        if (index < text.length) {
            subtitle.innerHTML += text.charAt(index);
            index++;
            setTimeout(typeWriter, 50);
        }
    }
    setTimeout(typeWriter, 1000);
    const musicBtn = document.getElementById('music-toggle');
    const audio = new Audio('assets/music.mp3'); 
    audio.loop = true;
    let isPlaying = false;

    musicBtn.addEventListener('click', () => {
        if (!isPlaying) {
            audio.play().catch(() => console.log("Interaction utilisateur requise pour l'audio"));
            musicBtn.innerHTML = '<i class="fas fa-pause"></i>';
        } else {
            audio.pause();
            musicBtn.innerHTML = '<i class="fas fa-music"></i>';
        }
        isPlaying = !isPlaying;
    });
    const observerOptions = { threshold: 0.2 };
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                if (entry.target.classList.contains('message__card')) {
                    triggerLuxeConfetti();
                }
                if (entry.target.classList.contains('reasons__list')) {
                    const items = entry.target.querySelectorAll('.reason__item');
                    items.forEach((item, i) => {
                        setTimeout(() => item.classList.add('visible'), i * 150);
                    });
                }
            }
        });
    }, observerOptions);

    document.querySelectorAll('.scroll-reveal, .reasons__list, .gallery__item').forEach(el => {
        revealObserver.observe(el);
    });
    const lightbox = document.getElementById('lightbox');
    const lightboxClose = document.getElementById('lightbox-close');
    const lightboxContainer = document.getElementById('lightbox-media-container');
    const lightboxCaption = document.getElementById('lightbox-caption');

    document.querySelectorAll('.gallery__item').forEach(item => {
        item.addEventListener('click', () => {
            const media = item.querySelector('img') || item.querySelector('video');
            const caption = item.querySelector('.gallery__overlay span').innerText;
            
            lightboxContainer.innerHTML = '';
            if (media.tagName === 'IMG') {
                const clone = media.cloneNode();
                lightboxContainer.appendChild(clone);
            } else {
                const video = document.createElement('video');
                video.src = media.querySelector('source').src;
                video.controls = true;
                video.autoplay = true;
                lightboxContainer.appendChild(video);
            }
            
            lightboxCaption.innerText = caption;
            lightbox.showModal();
        });
    });

    lightboxClose.addEventListener('click', () => lightbox.close());
    lightbox.addEventListener('click', (e) => { if (e.target === lightbox) lightbox.close(); });

    function initHeartParticles() {
        const canvas = document.getElementById('heart-particles-canvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        let hearts = [];

        function resize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        window.addEventListener('resize', resize);
        resize();

        class Heart {
            constructor() {
                this.reset();
            }
            reset() {
                this.x = Math.random() * canvas.width;
                this.y = canvas.height + Math.random() * 100;
                this.size = Math.random() * 15 + 5;
                this.speed = Math.random() * 1.5 + 0.5;
                this.opacity = Math.random() * 0.5 + 0.2;
                this.angle = Math.random() * Math.PI * 2;
            }
            draw() {
                ctx.save();
                ctx.translate(this.x, this.y);
                ctx.opacity = this.opacity;
                ctx.fillStyle = `rgba(233, 30, 99, ${this.opacity})`;
                ctx.beginPath();
                // Dessin d'un cœur simplifié en canvas
                ctx.moveTo(0, 0);
                ctx.bezierCurveTo(-this.size/2, -this.size/2, -this.size, this.size/3, 0, this.size);
                ctx.bezierCurveTo(this.size, this.size/3, this.size/2, -this.size/2, 0, 0);
                ctx.fill();
                ctx.restore();
            }
            update() {
                this.y -= this.speed;
                this.x += Math.sin(this.angle) * 0.5;
                if (this.y < -20) this.reset();
            }
        }

        for (let i = 0; i < 30; i++) hearts.push(new Heart());

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hearts.forEach(h => { h.update(); h.draw(); });
            requestAnimationFrame(animate);
        }
        animate();
    }


    function triggerLuxeConfetti() {
        const canvas = document.getElementById('confetti-canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        let particles = [];
        const colors = ['#e91e63', '#d4af37', '#880e4f', '#ffffff'];

        for (let i = 0; i < 150; i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height - canvas.height,
                size: Math.random() * 8 + 4,
                color: colors[Math.floor(Math.random() * colors.length)],
                speed: Math.random() * 3 + 2,
                angle: Math.random() * 6
            });
        }

        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach((p, i) => {
                p.y += p.speed;
                p.x += Math.sin(p.angle) * 1;
                ctx.fillStyle = p.color;
                ctx.fillRect(p.x, p.y, p.size, p.size);
                
                if (p.y > canvas.height) particles[i].y = -10;
            });
            if (particles[0].y < canvas.height * 2) requestAnimationFrame(draw);
        }
        draw();
    }

    

function customizePage(config) {
    if(config.title) document.querySelector('.hero__title').innerHTML = config.title;
    if(config.primaryColor) document.documentElement.style.setProperty('--rose-primary', config.primaryColor);}