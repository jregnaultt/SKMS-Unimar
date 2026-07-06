# Advanced Select Component Design: `x-advanced-select`

This document details the architectural specification, design decisions, and implementation blueprint for a custom searchable select component in SKMS-Unimar.

---

## 1. Project Context & Purpose

The **Scientific Knowledge Management System (SKMS)** requires a highly interactive form input to select entity relations (e.g., Tutors, Students, Research Lines, and Academic Programs). Standard browser `<select>` dropdowns are insufficient for lists exceeding dozens of items.

To ensure consistency with the stack (Blade, Tailwind CSS, Alpine.js) and respect Venezuelan low-bandwidth environments, we avoid adding jQuery or bulky third-party libraries (like Select2). Instead, we specify a custom component built from scratch.

---

## 2. Understanding Summary

* **What is being built:** A reusable Blade component (`<x-advanced-select>`) wrapping an Alpine.js state machine that provides searching, keyboard navigation, and badge rendering for single/multi-select modes.
* **Why it exists:** To replace standard browser dropdowns with a modern, searchable selection interface.
* **Who it is for:** Research Coordinators, Students, and Admins who manage research work metadata, tutor assignments, and academic periods.
* **Key constraints:**
  * Strict integration with Blade, Tailwind CSS, and Alpine.js.
  * AJAX requests managed via the project's globally configured **Axios** instance (incorporating CSRF tokens).
  * Optimizations (debouncing) for slow connections.
* **Explicit non-goals:**
  * Integrating jQuery or jQuery-dependent libraries.
  * Creating a generic third-party package.

---

## 3. Assumptions

1. **JSON Endpoints:** Laravel controllers providing data to the component will return a flat JSON array format: `[ { "id": 1, "text": "Dr. Jean Regnault" }, ... ]`.
2. **Laravel Form Submission:** The component outputs standard hidden inputs (e.g., `<input type="hidden" name="tutors[]" value="1">`) to support conventional multi-value form submission (`$request->input('tutors')`).
3. **Accessibility:** Navigation via `ArrowUp`, `ArrowDown`, `Enter`, and `Escape` is natively handled by Alpine directives.

---

## 4. Decision Log

### Decision 1: Technology Stack selection
* **Decided:** Custom component utilizing Alpine.js + Tailwind CSS.
* **Alternatives considered:** jQuery + Select2, Vanilla Tom Select, Vanilla Choices.js.
* **Why chosen:** Aligns with the project's strict requirement for server-side Blade rendering + minimal JS client footprint. Eliminates jQuery entirely, reducing page load weight.

### Decision 2: API Client for dynamic searches
* **Decided:** Axios client with a 250ms debounced watch on input.
* **Alternatives considered:** Fetch API, instant requests on keystroke.
* **Why chosen:** Axios is already configured with headers and CSRF logic in `bootstrap.js`. A 250ms debounce minimizes database queries and handles high latency network environments smoothly.

### Decision 3: Visual selected display
* **Decided:** Interactive badges inside the selection field for multi-select.
* **Alternatives considered:** Selected lists below the input field, dropdown checklists.
* **Why chosen:** Mimics the widely understood Select2 standard in an elegant Tailwind container.

---

## 5. Technical Blueprint

### Component Properties (Blade API)

```blade
<!-- Usage Example -->
<x-advanced-select 
    name="tutor_ids" 
    placeholder="Seleccione uno o más tutores..."
    multiple
    endpoint="/admin/autocomplete/tutors"
    :selected="[
        ['id' => 1, 'text' => 'Dr. Jean Regnault'],
        ['id' => 2, 'text' => 'Dra. María Pérez']
    ]"
/>
```

### Script & Controller Structure (Alpine.js)

```javascript
document.addEventListener('alpine:init', () => {
    Alpine.data('advancedSelect', (config) => ({
        open: false,
        search: '',
        options: config.options || [],
        selected: config.selected || [],
        highlightedIndex: -1,
        loading: false,
        endpoint: config.endpoint || '',
        multiple: config.multiple || false,
        
        init() {
            // Watch search query to run debounced AJAX queries
            this.$watch('search', value => {
                if (this.endpoint && value.length >= 2) {
                    this.fetchOptions(value);
                }
            });
        },

        debounce(func, wait) {
            let timeout;
            return (...args) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        },

        fetchOptions: Alpine.debounce(function(query) {
            this.loading = true;
            axios.get(this.endpoint, { params: { q: query } })
                .then(response => {
                    this.options = response.data;
                    this.highlightedIndex = 0;
                })
                .catch(error => {
                    console.error('Error fetching options:', error);
                })
                .finally(() => {
                    this.loading = false;
                });
        }, 250),

        selectOption(option) {
            if (this.multiple) {
                if (!this.selected.some(item => item.id === option.id)) {
                    this.selected.push(option);
                }
            } else {
                this.selected = [option];
                this.open = false;
            }
            this.search = '';
        },

        removeOption(id) {
            this.selected = this.selected.filter(item => item.id !== id);
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
        }
    }));
});
```
