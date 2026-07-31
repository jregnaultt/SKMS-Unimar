import { driver } from "driver.js";
import { getTourSteps } from "../tours";

export default () => ({
    sidebarOpen: false,
    driverInstance: null,

    init() {
        this.$nextTick(() => {
            const activeTour = localStorage.getItem('skms_active_tour');
            const savedStep = localStorage.getItem('skms_tour_step');

            if (activeTour) {
                // Clear immediately to avoid loops
                localStorage.removeItem('skms_active_tour');
                localStorage.removeItem('skms_tour_step');
                
                // Start the tour at the saved step
                this.start(activeTour, parseInt(savedStep || 0));
            }
        });
    },

    start(role, startAtStep = 0) {
        const steps = getTourSteps(role);
        if (!steps || steps.length === 0) {
            Swal.fire({
                title: 'Guía no disponible',
                text: 'No hay elementos del recorrido disponibles para este rol en esta pantalla.',
                icon: 'info',
                confirmButtonText: 'Entendido',
                customClass: {
                    confirmButton: 'bg-[#0d4d98] text-white rounded-xl px-4 py-2 text-sm font-bold'
                }
            });
            return;
        }

        this.driverInstance = driver({
            showProgress: true,
            allowClose: true,
            nextBtnText: 'Siguiente',
            prevBtnText: 'Anterior',
            doneBtnText: 'Finalizar',
            steps: steps,
            onDestroyed: () => {
                const transitioning = localStorage.getItem('skms_transitioning');
                if (transitioning === 'true') {
                    localStorage.removeItem('skms_transitioning');
                } else {
                    localStorage.removeItem('skms_active_tour');
                    localStorage.removeItem('skms_tour_step');
                }
            }
        });

        this.driverInstance.drive(startAtStep);
    }
});
