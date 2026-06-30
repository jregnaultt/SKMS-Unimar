export default () => ({
    query: '',
    results: [],
    isLoading: false,
    showDropdown: false,

    init() {
        this.$watch('query', (value) => {
            if (value.trim().length >= 3) {
                this.search();
            } else {
                this.results = [];
                this.showDropdown = false;
            }
        });
    },

    async search() {
        this.isLoading = true;
        this.showDropdown = true;
        const payload = { titulo: this.query };

        try {
            // Intento con el método HTTP QUERY
            const response = await axios({
                method: 'QUERY',
                url: '/catalog/query',
                data: payload,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            // Si la respuesta tiene la estructura paginada estándar de Laravel
            this.results = response.data.data || [];
        } catch (error) {
            // Fallback en caso de que QUERY falle o no esté soportado en la red/servidor (405 o 501)
            if (!error.response || error.response.status === 405 || error.response.status === 501) {
                try {
                    const fallbackResponse = await axios.post('/catalog/query', payload, {
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        }
                    });
                    this.results = fallbackResponse.data.data || [];
                } catch (fallbackError) {
                    console.error('Búsqueda interactiva fallida en fallback:', fallbackError);
                }
            } else {
                console.error('Error en búsqueda interactiva:', error);
            }
        } finally {
            this.isLoading = false;
        }
    },

    closeDropdown() {
        setTimeout(() => {
            this.showDropdown = false;
        }, 200); // Pequeño retraso para permitir hacer click en los resultados
    }
});
