export default (config = {}) => {
    let debounceTimer;
    return {
        open: false,
        search: '',
        options: config.options || [],
        selected: config.selected || [],
        highlightedIndex: -1,
        loading: false,
        endpoint: config.endpoint || '',
        multiple: config.multiple || false,
        optionsWatch: config.optionsWatch || '',
        disabled: config.disabled || false,

        init() {
            // Setup watch on search term to fetch options dynamically
            this.$watch('search', (value) => {
                if (value.trim().length >= 2) {
                    this.debouncedSearch();
                } else if (value.trim().length === 0) {
                    // Fetch initial/default options when query is cleared
                    this.fetchOptions();
                }
            });

            // Fetch initial 10 options when dropdown is opened for the first time
            this.$watch('open', (isOpen) => {
                if (isOpen && this.options.length === 0 && this.endpoint && !this.disabled) {
                    this.fetchOptions();
                }
            });

            // Watch external Alpine scope variable if configured (for dynamic option syncing)
            if (this.optionsWatch) {
                this.$watch(this.optionsWatch, (newVal) => {
                    // Map external objects to standard { id, text } shape
                    this.options = (newVal || []).map(item => {
                        const tutorObj = item.tutor || {};
                        return {
                            id: item.tutor_id ?? tutorObj.id ?? item.id ?? item,
                            text: tutorObj.name ?? item.name ?? item.text ?? String(item)
                        };
                    });
                    
                    // If selection is no longer in updated options, clear it
                    if (this.selected.length > 0 && !this.options.some(opt => opt.id === this.selected[0].id)) {
                        this.selected = [];
                        this.dispatchChangeEvent();
                    }
                });
            }
        },

        debouncedSearch() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                this.fetchOptions();
            }, 250);
        },

        async fetchOptions() {
            if (this.disabled) return;

            if (!this.endpoint) {
                // Local static search in options
                const query = this.search.toLowerCase();
                this.options = (config.options || []).filter(opt => 
                    opt.text.toLowerCase().includes(query)
                );
                this.highlightedIndex = this.options.length > 0 ? 0 : -1;
                return;
            }

            this.loading = true;
            try {
                const response = await axios.get(this.endpoint, {
                    params: { q: this.search }
                });
                this.options = response.data;
                this.highlightedIndex = this.options.length > 0 ? 0 : -1;
            } catch (error) {
                console.error('Error fetching autocomplete options:', error);
            } finally {
                this.loading = false;
            }
        },

        selectOption(option) {
            if (this.disabled) return;

            if (this.multiple) {
                if (!this.selected.some(item => item.id === option.id)) {
                    this.selected.push(option);
                }
            } else {
                this.selected = [option];
                this.open = false;
            }
            this.search = '';
            this.dispatchChangeEvent();
        },

        removeOption(id) {
            if (this.disabled) return;

            this.selected = this.selected.filter(item => item.id !== id);
            this.dispatchChangeEvent();
        },

        highlightNext() {
            if (this.options.length === 0) return;
            this.highlightedIndex = (this.highlightedIndex + 1) % this.options.length;
        },

        highlightPrev() {
            if (this.options.length === 0) return;
            this.highlightedIndex = (this.highlightedIndex - 1 + this.options.length) % this.options.length;
        },

        selectHighlighted() {
            if (this.highlightedIndex >= 0 && this.highlightedIndex < this.options.length) {
                this.selectOption(this.options[this.highlightedIndex]);
            }
        },

        dispatchChangeEvent() {
            this.$nextTick(() => {
                // Dispatch change event on the container element so parents can catch it
                this.$el.dispatchEvent(new CustomEvent('change', {
                    bubbles: true,
                    detail: { value: this.selected }
                }));
            });
        }
    };
};
