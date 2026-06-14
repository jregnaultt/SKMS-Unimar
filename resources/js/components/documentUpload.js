export default (allResearchLines = []) => ({
    file: null,
    isUploading: false,
    uploadProgress: 0,
    statusMessage: '',
    fileId: '',
    action: 'draft',
    academicProgramId: '',
    researchLineId: '',
    productionTypeId: '',
    academicPeriodId: '',
    newTag: '',
    keywordList: [],
    allLines: allResearchLines,
    filteredResearchLines: [],
    metadata: {
        title: '',
        abstract: '',
        keywords: '',
        authors: '',
        tutor: ''
    },
    
    init() {
        const userId = document.head.querySelector('meta[name="user-id"]')?.content;
        
        if (userId && window.Echo) {
            window.Echo.private(`user.${userId}`)
                .listen('MetadataExtracted', (e) => {
                    this.isUploading = false;
                    this.statusMessage = '¡Metadatos extraídos con éxito!';
                    this.fileId = e.fileId || '';
                    this.metadata.title = e.metadata.title || '';
                    this.metadata.abstract = e.metadata.abstract || '';
                    this.metadata.keywords = e.metadata.keywords || '';
                    this.metadata.authors = e.metadata.authors || '';
                    this.metadata.tutor = e.metadata.tutor || '';

                    // Convert comma separated keywords from IA into visual chips
                    if (e.metadata.keywords) {
                        this.keywordList = e.metadata.keywords
                            .split(',')
                            .map(k => k.trim())
                            .filter(k => k.length > 0);
                    }
                });
        }
    },

    handleFileSelect(event) {
        this.file = event.target.files[0];
        if (this.file) {
            this.uploadAndExtract();
        }
    },

    uploadAndExtract() {
        if (!this.file) return;

        this.isUploading = true;
        this.statusMessage = 'Subiendo y extrayendo metadatos...';
        this.uploadProgress = 0;

        let formData = new FormData();
        formData.append('documento', this.file);

        axios.post('/productions/extract', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: (progressEvent) => {
                let percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                this.uploadProgress = percentCompleted;
            }
        })
        .then(response => {
            this.statusMessage = 'Procesando el documento con Inteligencia Artificial...';
            // The fileId is returned instantly by the extract endpoint
            if (response.data && response.data.file_id) {
                this.fileId = response.data.file_id;
            }
        })
        .catch(error => {
            this.isUploading = false;
            this.statusMessage = 'Error al procesar el archivo.';
            console.error(error);
        });
    },

    filterResearchLines() {
        if (!this.academicProgramId) {
            this.filteredResearchLines = [];
            this.researchLineId = '';
            return;
        }
        this.filteredResearchLines = this.allLines.filter(
            line => line.academic_program_id == this.academicProgramId
        );
        this.researchLineId = ''; // Reset selection
    },

    addKeyword() {
        let tag = this.newTag.trim();
        // Remove trailing commas if added via comma key
        tag = tag.replace(/,+$/, '').trim();
        if (tag && !this.keywordList.includes(tag)) {
            this.keywordList.push(tag);
        }
        this.newTag = '';
    },

    removeKeyword(index) {
        this.keywordList.splice(index, 1);
    },

    submitForm(event) {
        // Double check required file
        if (!this.fileId) {
            event.preventDefault();
            alert('Por favor, sube un documento PDF o Word primero.');
            return false;
        }
        return true;
    }
});
