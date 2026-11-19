/**
 * SHOPPULSE - Three.js Interactive 3D Hero Scene
 * High-performance, 60fps WebGL floating geometry with mouse inertia & camera parallax
 */

(function () {
    'use strict';

    // Respect reduced-motion preferences
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const container = document.getElementById('hero-canvas-wrap');
    if (!container || typeof THREE === 'undefined') {
        return;
    }

    let scene, camera, renderer, animationFrameId;
    let shapes = [];
    let particleSystem;
    let mouse = { x: 0, y: 0, targetX: 0, targetY: 0 };
    let isVisible = true;
    let windowHalfX = container.clientWidth / 2;
    let windowHalfY = container.clientHeight / 2;

    function init() {
        // Scene setup
        scene = new THREE.Scene();

        // Camera setup
        camera = new THREE.PerspectiveCamera(
            45,
            container.clientWidth / container.clientHeight,
            0.1,
            1000
        );
        camera.position.set(0, 0, 32);

        // WebGL Renderer
        renderer = new THREE.WebGLRenderer({
            alpha: true,
            antialias: true,
            powerPreference: 'high-performance'
        });
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.toneMapping = THREE.ACESFilmicToneMapping;
        renderer.toneMappingExposure = 1.2;
        container.appendChild(renderer.domElement);

        // Lighting
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.9);
        scene.add(ambientLight);

        const pointLight1 = new THREE.PointLight(0x0066cc, 2.5, 60);
        pointLight1.position.set(15, 12, 10);
        scene.add(pointLight1);

        const pointLight2 = new THREE.PointLight(0x00a2ff, 2.0, 50);
        pointLight2.position.set(-15, -10, 8);
        scene.add(pointLight2);

        const accentLight = new THREE.PointLight(0xff4747, 1.8, 45);
        accentLight.position.set(5, -12, 12);
        scene.add(accentLight);

        // Materials: Translucent Frosted Glass / Ceramic Iridescence
        const materials = [
            new THREE.MeshPhysicalMaterial({
                color: 0x0066cc,
                metalness: 0.15,
                roughness: 0.1,
                transmission: 0.6,
                thickness: 1.2,
                transparent: true,
                opacity: 0.9
            }),
            new THREE.MeshPhysicalMaterial({
                color: 0x00a2ff,
                metalness: 0.2,
                roughness: 0.15,
                clearcoat: 1.0,
                clearcoatRoughness: 0.1
            }),
            new THREE.MeshStandardMaterial({
                color: 0xff4747,
                roughness: 0.3,
                metalness: 0.4
            }),
            new THREE.MeshPhysicalMaterial({
                color: 0xffffff,
                metalness: 0.05,
                roughness: 0.05,
                transmission: 0.85,
                transparent: true,
                opacity: 0.85
            })
        ];

        // Create Floating Floating Designer Shapes (Cube, Torus, Sphere, Dodecahedron)
        const geometries = [
            new THREE.TorusGeometry(3.5, 1.0, 24, 60),
            new THREE.IcosahedronGeometry(2.8, 1),
            new THREE.BoxGeometry(3.2, 3.2, 3.2),
            new THREE.SphereGeometry(2.2, 32, 32),
            new THREE.TorusKnotGeometry(2.0, 0.6, 64, 16)
        ];

        // Position coordinates for harmonic floating composition
        const positions = [
            { x: 5, y: 3, z: 2, rotSpeed: { x: 0.004, y: 0.006, z: 0.002 } },
            { x: -6, y: -4, z: -3, rotSpeed: { x: -0.005, y: 0.004, z: 0.003 } },
            { x: 9, y: -5, z: 0, rotSpeed: { x: 0.006, y: -0.005, z: 0.004 } },
            { x: -4, y: 5, z: 1, rotSpeed: { x: -0.003, y: 0.007, z: -0.002 } },
            { x: 1, y: -6, z: 4, rotSpeed: { x: 0.005, y: 0.005, z: 0.005 } }
        ];

        positions.forEach((pos, i) => {
            const geom = geometries[i % geometries.length];
            const mat = materials[i % materials.length];
            const mesh = new THREE.Mesh(geom, mat);
            mesh.position.set(pos.x, pos.y, pos.z);
            mesh.userData = {
                originPos: { ...pos },
                rotSpeed: pos.rotSpeed,
                floatSpeed: 0.0015 + (i * 0.0005),
                floatOffset: i * 1.5
            };
            scene.add(mesh);
            shapes.push(mesh);
        });

        // Add Ambient Particle Field (Sparkling dust)
        const particleCount = 120;
        const particleGeometry = new THREE.BufferGeometry();
        const particlePositions = new Float32Array(particleCount * 3);

        for (let i = 0; i < particleCount * 3; i += 3) {
            particlePositions[i] = (Math.random() - 0.5) * 45;
            particlePositions[i + 1] = (Math.random() - 0.5) * 35;
            particlePositions[i + 2] = (Math.random() - 0.5) * 30;
        }

        particleGeometry.setAttribute('position', new THREE.BufferAttribute(particlePositions, 3));
        const particleMaterial = new THREE.PointsMaterial({
            color: 0x0071dc,
            size: 0.35,
            transparent: true,
            opacity: 0.55
        });

        particleSystem = new THREE.Points(particleGeometry, particleMaterial);
        scene.add(particleSystem);

        // Event Listeners
        window.addEventListener('resize', onWindowResize);
        window.addEventListener('mousemove', onMouseMove);
        window.addEventListener('scroll', onScroll, { passive: true });

        // Pause rendering when scrolled out of view to save battery/GPU
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                isVisible = entry.isIntersecting;
                if (isVisible && !animationFrameId) {
                    animate();
                }
            });
        }, { threshold: 0.1 });
        observer.observe(container);

        animate();
    }

    function onMouseMove(event) {
        mouse.targetX = (event.clientX - windowHalfX) * 0.0012;
        mouse.targetY = (event.clientY - windowHalfY) * 0.0012;
    }

    function onScroll() {
        const scrollY = window.pageYOffset || document.documentElement.scrollTop;
        if (camera) {
            camera.position.y = -scrollY * 0.008;
        }
    }

    function onWindowResize() {
        if (!container || !renderer || !camera) return;
        windowHalfX = container.clientWidth / 2;
        windowHalfY = container.clientHeight / 2;
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.clientWidth, container.clientHeight);
    }

    let time = 0;
    function animate() {
        if (!isVisible) {
            animationFrameId = null;
            return;
        }

        animationFrameId = requestAnimationFrame(animate);
        time += 0.02;

        // Smooth mouse inertia lerping
        mouse.x += (mouse.targetX - mouse.x) * 0.05;
        mouse.y += (mouse.targetY - mouse.y) * 0.05;

        // Parallax camera tilt
        camera.position.x = mouse.x * 8;
        camera.lookAt(scene.position);

        // Animate floating shapes
        shapes.forEach((mesh) => {
            mesh.rotation.x += mesh.userData.rotSpeed.x;
            mesh.rotation.y += mesh.userData.rotSpeed.y;
            mesh.rotation.z += mesh.userData.rotSpeed.z;

            // Subtle vertical and horizontal floating bob
            const offset = mesh.userData.floatOffset;
            mesh.position.y = mesh.userData.originPos.y + Math.sin(time + offset) * 0.6;
            mesh.position.x = mesh.userData.originPos.x + Math.cos((time * 0.7) + offset) * 0.3;
        });

        // Rotate particle system slowly
        if (particleSystem) {
            particleSystem.rotation.y += 0.0008;
            particleSystem.rotation.x += 0.0004;
        }

        renderer.render(scene, camera);
    }

    // Clean teardown helper if page unloads
    window.addEventListener('beforeunload', () => {
        if (animationFrameId) cancelAnimationFrame(animationFrameId);
        if (renderer) renderer.dispose();
        shapes.forEach(m => {
            if (m.geometry) m.geometry.dispose();
            if (m.material) m.material.dispose();
        });
    });

    // Start initialization when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
