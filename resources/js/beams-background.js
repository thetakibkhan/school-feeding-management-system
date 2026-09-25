const intensityOpacity = { subtle: 0.7, medium: 0.85, strong: 1 };

function createBeam(width, height) {
    return {
        x: Math.random() * width * 1.5 - width * 0.25,
        y: Math.random() * height * 1.5 - height * 0.25,
        width: 30 + Math.random() * 60,
        length: height * 2.5,
        angle: -35 + Math.random() * 10,
        speed: 0.6 + Math.random() * 1.2,
        opacity: 0.12 + Math.random() * 0.16,
        hue: 190 + Math.random() * 70,
        pulse: Math.random() * Math.PI * 2,
        pulseSpeed: 0.02 + Math.random() * 0.03,
    };
}

function drawBeam(context, beam, intensity) {
    context.save();
    context.translate(beam.x, beam.y);
    context.rotate((beam.angle * Math.PI) / 180);

    const opacity = beam.opacity * (0.8 + Math.sin(beam.pulse) * 0.2) * intensityOpacity[intensity];
    const color = (alpha) => `hsla(${beam.hue}, 85%, 65%, ${alpha})`;
    const gradient = context.createLinearGradient(0, 0, 0, beam.length);

    gradient.addColorStop(0, color(0));
    gradient.addColorStop(0.1, color(opacity * 0.5));
    gradient.addColorStop(0.4, color(opacity));
    gradient.addColorStop(0.6, color(opacity));
    gradient.addColorStop(0.9, color(opacity * 0.5));
    gradient.addColorStop(1, color(0));

    context.fillStyle = gradient;
    context.fillRect(-beam.width / 2, 0, beam.width, beam.length);
    context.restore();
}

function resetBeam(beam, index, totalBeams, width, height) {
    const spacing = width / 3;
    beam.y = height + 100;
    beam.x = (index % 3) * spacing + spacing / 2 + (Math.random() - 0.5) * spacing * 0.5;
    beam.width = 100 + Math.random() * 100;
    beam.speed = 0.5 + Math.random() * 0.4;
    beam.hue = 190 + (index * 70) / totalBeams;
    beam.opacity = 0.2 + Math.random() * 0.1;
}

export function mountBeamsBackground(element, intensity = 'strong') {
    const canvas = element.querySelector('[data-beams-canvas]');
    const context = canvas?.getContext('2d');
    if (!canvas || !context || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return () => {};

    const beams = [];
    let animationFrame;

    const resize = () => {
        const ratio = window.devicePixelRatio || 1;
        const width = window.innerWidth;
        const height = window.innerHeight;
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        beams.length = 0;
        for (let index = 0; index < 30; index += 1) beams.push(createBeam(width, height));
    };

    const animate = () => {
        const width = window.innerWidth;
        const height = window.innerHeight;
        context.clearRect(0, 0, width, height);
        context.filter = 'blur(35px)';
        beams.forEach((beam, index) => {
            beam.y -= beam.speed;
            beam.pulse += beam.pulseSpeed;
            if (beam.y + beam.length < -100) resetBeam(beam, index, beams.length, width, height);
            drawBeam(context, beam, intensity);
        });
        animationFrame = window.requestAnimationFrame(animate);
    };

    resize();
    window.addEventListener('resize', resize);
    animate();

    return () => {
        window.removeEventListener('resize', resize);
        window.cancelAnimationFrame(animationFrame);
    };
}
