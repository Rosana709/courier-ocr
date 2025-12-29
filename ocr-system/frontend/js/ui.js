/**
 * Handles all UI transitions, event listeners, and rendering
 */
const UI = {
    // Views
    incomingView: document.getElementById('view-incoming'),
    outgoingView: document.getElementById('view-outgoing'),
    archiveView: document.getElementById('view-archive'),

    // Components
    dropZone: document.getElementById('drop-zone'),
    fileInput: document.getElementById('file-input'),
    processingState: document.getElementById('processing-state'),
    resultView: document.getElementById('result-view'),
    archiveList: document.getElementById('archive-list'),
    emptyArchive: document.getElementById('empty-archive'),
    imagePreview: document.getElementById('image-preview'),
    extractForm: document.getElementById('extract-form'),
    searchInput: document.getElementById('search-input'),

    // Outgoing Components
    generateForm: document.getElementById('generate-form'),
    genProcessingState: document.getElementById('gen-processing-state'),
    genResultView: document.getElementById('gen-result-view'),
    genEditForm: document.getElementById('gen-edit-form'),
    genRetryBtn: document.getElementById('gen-retry-btn'),
    genPdfBtn: document.getElementById('gen-pdf-btn'),
    genArchiveBtn: document.getElementById('gen-archive-btn'),
    themeToggle: document.getElementById('theme-toggle'),

    // Navigation
    navIncoming: document.getElementById('nav-incoming'),
    navOutgoing: document.getElementById('nav-outgoing'),
    navArchive: document.getElementById('nav-archive'),

    lastGeneratedMail: null,

    init() {
        this.initTheme();
        this.setupEventListeners();
        this.renderArchive();
        this.switchView('archive'); // Set default view
    },

    initTheme() {
        const theme = localStorage.getItem('theme') || 'light';
        if (theme === 'dark') {
            document.body.classList.add('dark-theme');
        } else {
            document.body.classList.remove('dark-theme');
        }
    },

    toggleTheme() {
        const isDark = document.body.classList.toggle('dark-theme');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    },

    setupEventListeners() {
        // Navigation
        this.navIncoming.addEventListener('click', () => this.switchView('incoming'));
        this.navOutgoing.addEventListener('click', () => this.switchView('outgoing'));
        this.navArchive.addEventListener('click', () => this.switchView('archive'));

        // Upload
        this.dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            this.dropZone.classList.add('drag-over');
        });

        this.dropZone.addEventListener('dragleave', () => {
            this.dropZone.classList.remove('drag-over');
        });

        this.dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            this.dropZone.classList.remove('drag-over');
            const file = e.dataTransfer.files[0];
            if (file && (file.type.startsWith('image/') || file.type === 'application/pdf')) {
                this.handleFileUpload(file);
            }
        });

        this.fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) this.handleFileUpload(file);
        });

        // Search
        this.searchInput.addEventListener('input', (e) => {
            this.renderArchive(e.target.value);
        });

        // Form Submits
        this.extractForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleFormSubmit();
        });

        this.generateForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleAIGeneration();
        });

        // Outgoing Actions
        this.genRetryBtn.addEventListener('click', () => {
            this.genResultView.classList.add('hidden');
            this.generateForm.classList.remove('hidden');
        });

        this.genPdfBtn.addEventListener('click', () => {
            const currentMail = this.getGenFormData();
            if (currentMail) {
                downloadMailPDF(currentMail);
            }
        });

        this.genArchiveBtn.addEventListener('click', () => {
            const currentMail = this.getGenFormData();
            if (currentMail) {
                saveLetter(currentMail);
                alert("Courrier généré et archivé !");
                this.switchView('archive');
            }
        });

        // Modal Close
        document.querySelector('.close-modal').addEventListener('click', () => {
            document.getElementById('modal').classList.add('hidden');
        });

        // Theme Toggle
        this.themeToggle.addEventListener('click', () => this.toggleTheme());
    },

    getGenFormData() {
        const formData = new FormData(this.genEditForm);
        return {
            senderService: formData.get('senderService'),
            receiverService: formData.get('receiverService'),
            date: formData.get('date'),
            letterNumber: formData.get('letterNumber'),
            subject: formData.get('subject'),
            importance: formData.get('importance'),
            body: formData.get('body'),
            type: 'sortant'
        };
    },

    switchView(viewName) {
        // Reset actives
        [this.navIncoming, this.navOutgoing, this.navArchive].forEach(btn => btn.classList.remove('active'));
        [this.incomingView, this.outgoingView, this.archiveView].forEach(view => view.classList.add('hidden'));

        if (viewName === 'incoming') {
            this.incomingView.classList.remove('hidden');
            this.navIncoming.classList.add('active');
        } else if (viewName === 'outgoing') {
            this.outgoingView.classList.remove('hidden');
            this.navOutgoing.classList.add('active');
        } else {
            this.archiveView.classList.remove('hidden');
            this.navArchive.classList.add('active');
            this.renderArchive();
        }
    },

    async handleFileUpload(file) {
        this.dropZone.classList.add('hidden');
        this.processingState.classList.remove('hidden');

        try {
            let processedFile = file;
            let previewSrc = '';

            if (file.type === 'application/pdf') {
                // Convert PDF to image for OCR and preview
                previewSrc = await this.convertPdfToImage(file);
                // Create a blob from the dataURL to send to the backend
                const response = await fetch(previewSrc);
                const blob = await response.blob();
                processedFile = new File([blob], file.name.replace('.pdf', '.png'), { type: 'image/png' });
            } else {
                previewSrc = await new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onload = (e) => resolve(e.target.result);
                    reader.readAsDataURL(file);
                });
            }

            this.imagePreview.src = previewSrc;

            const data = await extractDataFromImage(processedFile);
            this.displayResults(data);
        } catch (error) {
            console.error(error);
            alert("Erreur d'extraction : " + error.message);
            this.resetUpload();
        }
    },

    /**
     * Converts the first page of a PDF to a PNG DataURL
     */
    async convertPdfToImage(file) {
        const arrayBuffer = await file.arrayBuffer();
        const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
        const page = await pdf.getPage(1);

        const viewport = page.getViewport({ scale: 2.0 }); // High resolution for OCR
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        canvas.height = viewport.height;
        canvas.width = viewport.width;

        await page.render({ canvasContext: context, viewport: viewport }).promise;
        return canvas.toDataURL('image/png');
    },

    displayResults(data) {
        this.processingState.classList.add('hidden');
        this.resultView.classList.remove('hidden');

        const form = this.extractForm;
        form.senderService.value = data.senderService || '';
        form.receiverService.value = data.receiverService || '';
        form.date.value = data.date || '';
        form.letterNumber.value = data.letterNumber || '';
        form.subject.value = data.subject || '';
        form.importance.value = data.importance || 'Normal';
        form.body.value = data.body || '';
    },

    handleFormSubmit() {
        const formData = new FormData(this.extractForm);
        const letterData = {
            senderService: formData.get('senderService'),
            receiverService: formData.get('receiverService'),
            date: formData.get('date'),
            letterNumber: formData.get('letterNumber'),
            subject: formData.get('subject'),
            importance: formData.get('importance'),
            body: formData.get('body'),
            imageData: this.imagePreview.src,
            type: 'entrant'
        };

        saveLetter(letterData);
        alert("Lettre enregistrée !");
        this.resetUpload();
        this.switchView('archive');
    },

    async handleAIGeneration() {
        const formData = new FormData(this.generateForm);
        const params = {
            senderService: formData.get('senderService'),
            receiverService: formData.get('receiverService'),
            letterNumber: formData.get('letterNumber'),
            importance: formData.get('importance'),
            prompt: formData.get('prompt')
        };

        this.generateForm.classList.add('hidden');
        this.genProcessingState.classList.remove('hidden');

        try {
            const result = await generateMailContent(params);

            this.genProcessingState.classList.add('hidden');
            this.genResultView.classList.remove('hidden');

            // Populate the edit form
            const form = this.genEditForm;
            form.senderService.value = result.senderService || '';
            form.receiverService.value = result.receiverService || '';
            form.date.value = result.date || '';
            form.letterNumber.value = result.letterNumber || '';
            form.subject.value = result.subject || '';
            form.importance.value = result.importance || 'Normal';
            form.body.value = result.body || '';

        } catch (error) {
            console.error(error);
            alert("Erreur de génération : " + error.message);
            this.genProcessingState.classList.add('hidden');
            this.generateForm.classList.remove('hidden');
        }
    },

    resetUpload() {
        this.dropZone.classList.remove('hidden');
        this.processingState.classList.add('hidden');
        this.resultView.classList.add('hidden');
        this.extractForm.reset();
        this.imagePreview.src = '';
    },

    renderArchive(query = '') {
        const letters = searchLetters(query);
        this.archiveList.innerHTML = '';

        if (letters.length === 0) {
            this.emptyArchive.classList.remove('hidden');
            return;
        }

        this.emptyArchive.classList.add('hidden');
        letters.forEach(letter => {
            const card = document.createElement('div');
            card.className = 'letter-card glass';

            const importanceClass = (letter.importance || '').toLowerCase().includes('urgent') ? 'tag-urgent' : 'tag-normal';
            const typeLabel = letter.type === 'sortant' ? 'Sortie' : 'Entrée';
            const typeClass = letter.type === 'sortant' ? 'tag-outgoing' : 'tag-incoming';

            card.innerHTML = `
                <div class="card-tags">
                    <span class="card-tag ${typeClass}">${typeLabel}</span>
                    <span class="card-tag ${importanceClass}">${letter.importance}</span>
                </div>
                <div class="card-title">${letter.subject}</div>
                <div class="card-info">
                    <div><strong>Service</strong> <span>${letter.type === 'sortant' ? letter.receiverService : letter.senderService}</span></div>
                    <div><strong>Référence</strong> <span>${letter.letterNumber}</span></div>
                    <div><strong>Date</strong> <span>${letter.date}</span></div>
                </div>
                <div class="card-actions">
                    <button class="action-btn view-btn" data-id="${letter.id}">Ouvrir</button>
                    ${letter.type === 'sortant' ? `<button class="action-btn pdf-btn" data-id="${letter.id}">PDF</button>` : ''}
                    <button class="action-btn delete-btn" data-id="${letter.id}">✕</button>
                </div>
            `;

            card.querySelector('.view-btn').onclick = () => this.showDetails(letter);

            if (letter.type === 'sortant') {
                card.querySelector('.pdf-btn').onclick = (e) => {
                    e.stopPropagation();
                    downloadMailPDF(letter);
                };
            }

            card.querySelector('.delete-btn').onclick = (e) => {
                e.stopPropagation();
                if (confirm("Supprimer cette lettre ?")) {
                    deleteLetter(letter.id);
                    this.renderArchive(this.searchInput.value);
                }
            };

            this.archiveList.appendChild(card);
        });
    },

    showDetails(letter) {
        const modal = document.getElementById('modal');
        const modalBody = document.getElementById('modal-body');

        modalBody.innerHTML = `
            <div class="modal-header">
                <div class="card-tags">
                    <span class="card-tag ${letter.type === 'sortant' ? 'tag-outgoing' : 'tag-incoming'}">${letter.type === 'sortant' ? 'Sortie' : 'Entrée'}</span>
                    <span class="card-tag ${(letter.importance || '').toLowerCase().includes('urgent') ? 'tag-urgent' : 'tag-normal'}">${letter.importance}</span>
                </div>
                <h2 style="font-size: 2rem; margin: 1.5rem 0;">${letter.subject}</h2>
            </div>
            
            <div class="detail-grid">
                <div class="detail-item"><strong>Expéditeur</strong> ${letter.senderService}</div>
                <div class="detail-item"><strong>Destinataire</strong> ${letter.receiverService}</div>
                <div class="detail-item"><strong>Date du document</strong> ${letter.date}</div>
                <div class="detail-item"><strong>Numéro de référence</strong> ${letter.letterNumber}</div>
            </div>

            <div class="detail-body">
                <h3>Contenu du courrier</h3>
                <pre style="white-space: pre-wrap; font-family: inherit;">${letter.body}</pre>
            </div>

            ${letter.imageData ? `
                <div class="detail-img" style="margin-top: 2.5rem;">
                    <h3 style="opacity: 0.7; margin-bottom: 1rem;">Document Source</h3>
                    <img src="${letter.imageData}" style="width: 100%; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                </div>
            ` : ''}

            <div class="form-actions" style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--border);">
                ${letter.type === 'sortant' ? `<button class="primary-btn" onclick="UI.downloadPDFFromModal('${letter.id}')">Télécharger le PDF</button>` : ''}
                <button class="secondary-btn" onclick="document.getElementById('modal').classList.add('hidden')">Fermer</button>
            </div>
        `;

        modal.classList.remove('hidden');
    },

    downloadPDFFromModal(id) {
        const letter = searchLetters('').find(l => l.id === id);
        if (letter) downloadMailPDF(letter);
    }
};

window.UI = UI; // Expose to global for onclick handlers in modal
