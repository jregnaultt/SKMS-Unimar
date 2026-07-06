# Implementation Plan: Custom Advanced Select Component (`x-advanced-select`)

This plan outlines the step-by-step phases to build, integrate, and test the custom searchable select component in SKMS-Unimar.

---

## Phase 1: Frontend Script and Component Registration

### Step 1.1: Create Alpine.js Component
Create the JavaScript logic in `resources/js/components/advancedSelect.js`.
* Implement state attributes (`open`, `search`, `options`, `selected`, `highlightedIndex`, `loading`).
* Add the Axios dynamic query method with a 250ms debounce watch.
* Add keyboard navigation event handlers for list scrolling and option selection.

### Step 1.2: Register in `app.js`
Modify `resources/js/app.js` to:
* Import `advancedSelect` from `./components/advancedSelect`.
* Register it: `Alpine.data('advancedSelect', advancedSelect)`.

---

## Phase 2: Blade Component View

### Step 2.1: Create the Blade Template
Create `resources/views/components/advanced-select.blade.php`.
* Incorporate Tailwind CSS styles to emulate an input field.
* Render dismissible selected badges inside the input if `multiple` is true.
* Implement the custom absolute dropdown menu styled with high-fidelity academic borders and shadow patterns.
* Embed hidden `<input type="hidden" name="{{ $name }}[]" :value="item.id">` elements synchronized with Alpine's `selected` array.

---

## Phase 3: Controller Search Endpoints

### Step 3.1: Add User Search Endpoint
Add a route `/admin/users/search` mapping to a new `search` method in `App\Http\Controllers\Admin\AdminUserController`.
* The endpoint should filter users by `role` if passed (e.g. `role=Tutor` or `role=Estudiante`) and search by `name`, `email`, and `cedula`.
* Limit search results to 10-15 rows to maximize load times under low-bandwidth constraints.

### Step 3.2: Register the Route
Add the GET route inside the authenticated admin group in `routes/web.php`.

---

## Phase 4: Integration in Views

### Step 4.1: Integrate in Academic Period management
Update the views in `resources/views/admin/periods/edit.blade.php`:
* Swap the standard tutor select element for `<x-advanced-select name="tutor_id" endpoint="/admin/users/search?role=Tutor" ... />`.
* Swap the student enrollment select for `<x-advanced-select name="student_id" endpoint="/admin/users/search?role=Estudiante" ... />`.

---

## Phase 5: Verification and Testing

### Step 5.1: Write Integration/Feature Test
Create or update a test in `tests/Feature/` (e.g., `tests/Feature/AdminUserSearchTest.php` or inside `tests/Feature/AcademicPeriodPlanningTest.php`):
* Assert the autocomplete endpoints return correctly formatted JSON.
* Ensure role restrictions (e.g., tutor-only results) are properly applied by the controller.
