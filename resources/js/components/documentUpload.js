export default () => ({
    file: null,
    isUploading: false,
    uploadProgress: 0,
    statusMessage: '',
    metadata: {
        title: '',
        abstract: '',
        keywords: '',
        authors: '',
        tutor: ''
    },
    
    init() {
        // userId should be passed via x-data initialization or a meta tag
        const userId = document.head.querySelector('meta[name="user-id"]')?.content;
        
        if (userId && window.Echo) {
            window.Echo.private(`user.${userId}`)
                .listen('MetadataExtracted', (e) => {
                    this.isUploading = false;
                    this.statusMessage = '¡Metadatos extraídos con éxito!';
                    this.metadata.title = e.metadata.title || '';
                    this.metadata.abstract = e.metadata.abstract || '';
                    this.metadata.keywords = e.metadata.keywords || '';
                    this.metadata.authors = e.metadata.authors || '';
                    this.metadata.tutor = e.metadata.tutor || '';
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
            // The rest is handled by Echo
        })
        .catch(error => {
            this.isUploading = false;
            this.statusMessage = 'Error al procesar el archivo.';
            console.error(error);
        });
    }
});
