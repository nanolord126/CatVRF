document.addEventListener('alpine:init', () => {
    Alpine.data('kitchenConfigurator', (template) => ({
        step: 1,
        template: template,
        config: {
            layout: 'linear',
            widthA: 3600,
            heightTotal: 2400,
            facadeMaterial: 'mdf',
            worktop: 'acryl',
            colors: {
                bottom: '#1e293b',
                top: '#475569',
                worktop: '#94a3b8'
            }
        },
        selectedModules: [],
        totalPrice: 0,
        totalWeight: 0,
        
        moduleLibrary: {
            bottom: [
                { type: 'drawer', name: 'Модуль с ящиками 600', width: 600, height: 720, price: 1540000, weight: 28000 },
                { type: 'oven', name: 'Пенал под духовку 600', width: 600, height: 2100, price: 2200000, weight: 45000 },
                { type: 'cabinet', name: 'Шкаф нижний 800', width: 800, height: 720, price: 1210000, weight: 32000 }
            ],
            top: [
                { type: 'wall', name: 'Шкаф верхний 600', width: 600, height: 720, price: 850000, weight: 15000 },
                { type: 'shelf', name: 'Полка открытая 600', width: 600, height: 360, price: 420000, weight: 8000 }
            ]
        },

        init() {
            this.$watch('selectedModules', () => this.calculateTotals());
            this.$watch('config', () => this.calculateTotals());
        },

        addModule(tier, moduleData) {
            const lastModule = this.selectedModules[this.selectedModules.length - 1];
            const x = lastModule ? (lastModule.x + lastModule.width) : 50;
            const y = (tier === 'bottom') ? 340 - moduleData.height : 100;

            this.selectedModules.push({
                ...moduleData,
                tier: tier,
                x: x,
                y: y,
                isAnimating: true
            });

            setTimeout(() => {
                const idx = this.selectedModules.findIndex(m => m.isAnimating);
                if(idx !== -1) this.selectedModules[idx].isAnimating = false;
            }, 600);
        },

        calculateTotals() {
            let price = this.selectedModules.reduce((acc, m) => acc + m.price, 0);
            let weight = this.selectedModules.reduce((acc, m) => acc + m.weight, 0);

            // Множители материалов
            if (this.config.facadeMaterial === 'mdf') price *= 1.25;
            if (this.config.worktop === 'quartz') price += 8500000;

            this.totalPrice = Math.round(price);
            this.totalWeight = Math.round(weight);
        },

        nextStep() {
            if (this.step < 5) this.step++;
        },

        formatPrice(kopeks) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' })
                .format(kopeks / 100);
        },

        formatWeight(grams) {
            return (grams / 1000).toFixed(1) + ' кг';
        },

        async saveProject() {
            const response = await fetch('/account/configurator/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    template_id: this.template.id,
                    project_name: 'Кухня Мечты ' + new Date().toLocaleDateString(),
                    payload: {
                        config: this.config,
                        modules: this.selectedModules
                    },
                    total_price: this.totalPrice,
                    total_weight: this.totalWeight
                })
            });

            const result = await response.json();
            if (result.success) {
                alert('Проект сохранен! Correlation ID: ' + result.correlation_id);
                window.location.href = '/account/orders/create?config=' + result.uuid;
            }
        }
    }));

    Alpine.data('brickCalculator', () => ({
        wall: { length: 10, height: 3 },
        config: {
            wallThicknessCoef: 1, // в кирпичах
            jointThickness: 10,  // мм
            wasteFactor: 5       // %
        },
        brick: {
            type: 'single',
            l: 250, w: 120, h: 65,
            density: 1800 // кг/м3
        },
        results: {
            brickCount: 0,
            brickVolume: 0,
            mortarVolume: 0,
            totalWeight: 0
        },

        init() {
            this.$watch('wall', () => this.calculate());
            this.$watch('config', () => this.calculate());
            this.$watch('brick', () => this.calculate());
            this.calculate();
        },

        updateBrick() {
            const specs = {
                single: { h: 65 },
                one_and_half: { h: 88 },
                double: { h: 138 }
            };
            this.brick.h = specs[this.brick.type].h;
        },

        calculate() {
            const area = this.wall.length * this.wall.height;
            const wallThickness = this.config.wallThicknessCoef * 0.25; // м
            const volume = area * wallThickness;

            // Расчет с учетом швов (упрощенная формула СНиП)
            // Объем одного кирпича со швами
            const brickVolWithJoints = (this.brick.l + this.config.jointThickness) * 
                                       (this.brick.h + this.config.jointThickness) * 
                                       (this.brick.w) / 1000000000;

            let count = volume / brickVolWithJoints;
            count *= (1 + this.config.wasteFactor / 100);

            this.results.brickCount = Math.ceil(count);
            this.results.brickVolume = (this.results.brickCount * (this.brick.l * this.brick.w * this.brick.h) / 1000000000).toFixed(2);
            this.results.mortarVolume = (volume - this.results.brickVolume).toFixed(2);
            this.results.totalWeight = ((volume * this.brick.density) / 1000).toFixed(1);
        }
    }));

    // --- Wardrobe Configurator ---
    Alpine.data('wardrobeConfigurator', (template) => ({
        config: {
            width: 1800,
            height: 2400,
            depth: 600,
            sections: 3,
            material: 'mdf_premium',
            internalConfig: [
                { type: 'shelves', qty: 4 },
                { type: 'rod', qty: 1 },
                { type: 'drawers', qty: 3 }
            ]
        },
        get totalPrice() {
            let base = (this.config.width * this.config.height / 10000) * 850;
            if (this.config.material === 'mdf_premium') base *= 1.4;
            return Math.round(base);
        },
        saveWardrobe() {
            console.log('Saving wardrobe...', this.config);
            // Logic for DB save
        },
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(price / 100);
        }
    }));

    // --- Stairs Configurator ---
    Alpine.data('stairsConfigurator', (template) => ({
        config: {
            type: 'straight',
            height: 3000,
            stepCount: 15,
            frameMaterial: 'wood'
        },
        get results() {
            return {
                stepHeight: Math.round(this.config.height / this.config.stepCount),
                stepWidth: 280,
                angle: Math.round(Math.atan((this.config.height / this.config.stepCount) / 280) * (180 / Math.PI))
            };
        },
        get totalPrice() {
            let base = this.config.stepCount * 450000;
            if (this.config.type === 'spiral') base *= 2.5;
            if (this.config.frameMaterial === 'metal') base *= 1.2;
            return Math.round(base);
        },
        saveStairs() {
            console.log('Generating blueprints...', this.config);
        },
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(price / 100);
        }
    }));

    // --- Flooring Calculator ---
    Alpine.data('flooringCalculator', (template) => ({
        config: {
            width: 5000,
            height: 4000,
            material: 'laminate',
            waste: 5,
            is_heated: false
        },
        get results() {
            let area = (this.config.width * this.config.height) / 1000000;
            let totalArea = area * (1 + (this.config.waste / 100));
            let packSq = this.config.material === 'laminate' ? 2.2 : 1.44;
            return {
                area: area.toFixed(2),
                packsQty: Math.ceil(totalArea / packSq),
                weight: Math.round(totalArea * (this.config.material === 'tiles' ? 18 : 8))
            };
        },
        get totalPrice() {
            let pricePerM2 = this.config.material === 'laminate' ? 145000 : 280000;
            if (this.config.is_heated) pricePerM2 += 120000;
            return Math.round((this.config.width * this.config.height / 1000000) * pricePerM2 * (1 + this.config.waste / 100));
        },
        saveFlooring() {
            console.log('Adding to cart...', this.config);
        },
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(price / 100);
        }
    }));

    // --- Roof Calculator ---
    Alpine.data('roofCalculator', (template) => ({
        config: {
            type: 'gable',
            width: 8000,
            length: 12000,
            pitch: 30,
            material: 'metal'
        },
        get results() {
            const halfW = this.config.width / 2;
            const rafterL = halfW / Math.cos(this.config.pitch * Math.PI / 180);
            const area = (rafterL * 2 * this.config.length) / 1000000;
            return {
                area: area.toFixed(2),
                rafterLength: Math.round(rafterL),
                snowLoad: 180,
                totalWeight: Math.round(area * (this.config.material === 'metal' ? 5 : 45)),
                unitsQty: Math.ceil(area / (this.config.material === 'metal' ? 1.1 : 0.8))
            };
        },
        get totalPrice() {
            let pricePerM2 = this.config.material === 'metal' ? 85000 : 125000;
            if (this.config.material === 'ceramic') pricePerM2 = 450000;
            return Math.round(parseFloat(this.results.area) * pricePerM2);
        },
        saveRoof() { console.log('Roof data saved:', this.config); },
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(price / 100);
        }
    }));

    // --- Door Configurator ---
    Alpine.data('doorConfigurator', (template) => ({
        config: {
            width: 800,
            height: 2000,
            texture: 'oak',
            hardware: 'chrome',
            opening: 'left',
            hasGlass: false
        },
        get totalPrice() {
            let base = 1250000;
            if (this.config.texture === ' oak') base += 450000;
            if (this.config.texture === 'wenge') base += 850000;
            if (this.config.hardware === 'gold') base += 250000;
            if (this.config.hasGlass) base += 350000;
            return base;
        },
        saveDoor() { console.log('Door configured:', this.config); },
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(price / 100);
        }
    }));

    // --- Window Configurator ---
    Alpine.data('windowConfigurator', (template) => ({
        config: {
            width: 1200,
            height: 1400,
            profile: 'REHAU-70',
            glass: 'double',
            color: 'White',
            hardware: 'Roto-NT',
            mosquitoNet: false
        },
        get results() {
            let area = (this.config.width * this.config.height) / 1000000;
            let uValue = this.config.profile === 'REHAU-70' ? 0.72 : 0.64;
            if (this.config.glass === 'triple') uValue -= 0.12;
            return {
                area: area.toFixed(2),
                uValue: uValue.toFixed(2)
            };
        },
        get totalPrice() {
            const rates = { 'REHAU-70': 850000, 'VEKA-82': 1150000, 'SALAMANDER': 1400000 };
            let base = parseFloat(this.results.area) * rates[this.config.profile];
            if (this.config.glass === 'triple') base *= 1.35;
            if (this.config.mosquitoNet) base += 250000;
            return Math.round(base);
        },
        saveWindow() { console.log('Window data saved'); },
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(price / 100);
        }
    }));

    // --- Foundation Calculator ---
    Alpine.data('foundationCalculator', (template) => ({
        config: {
            foundationType: 'slab',
            depth: 300,
            concreteGrade: 'M350',
            armature: 12,
            waterproof: true,
            insulation: false
        },
        get results() {
            const area = 100; // Mock 10x10 house
            const volume = (area * this.config.depth) / 1000;
            const armatureWeight = (volume * 80) / 1000;
            return {
                volume: volume.toFixed(1),
                armatureWeight: armatureWeight.toFixed(2),
                bearingCapacity: this.config.concreteGrade === 'M350' ? 25000 : 20000
            };
        },
        get totalPrice() {
            let vol = parseFloat(this.results.volume);
            let aw = parseFloat(this.results.armatureWeight);
            let base = (vol * 650000) + (aw * 8500000);
            if (this.config.waterproof) base += 4500000;
            return Math.round(base);
        },
        saveFoundation() { console.log('Foundation estimate generated'); },
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(price / 100);
        }
    }));

    // --- Heating Calculator ---
    Alpine.data('heatingCalculator', (template) => ({
        config: {
            area: 50,
            step: 150,
            pipeType: 'PEX-A',
            manifoldLoops: 4,
            insulation: true,
            automations: false
        },
        get results() {
            const length = this.config.area / (this.config.step / 1000);
            return {
                pipeLength: Math.round(length),
                heatOutput: Math.round(this.config.area * 75),
                maxLength: 80,
                flowRate: 1.5
            };
        },
        get totalPrice() {
            let base = (this.results.pipeLength * 12500) + (this.config.manifoldLoops * 450000);
            if (this.config.insulation) base += this.config.area * 45000;
            return Math.round(base);
        },
        generatePipePath() {
            const stepPx = this.config.step / 10;
            let path = 'M 60 60 ';
            for (let i = 0; i < 350; i += stepPx) {
                path += `L ${400 - i} ${60 + i} L ${400 - i} ${400 - i} `;
            }
            return path;
        },
        saveHeating() { console.log('Heating project ready'); },
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(price / 100);
        }
    }));
});
