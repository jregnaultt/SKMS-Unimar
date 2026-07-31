import { driver } from "driver.js";

const getCatalogSteps = () => {
    return [
        {
            element: '#catalog-search-card',
            popover: {
                title: 'Buscador Científico',
                description: 'Escribe palabras clave, autores o títulos para realizar una búsqueda a texto completo de inmediato.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '#catalog-filters-sidebar',
            popover: {
                title: 'Filtros Avanzados',
                description: 'Filtra las obras por autor, período, programa académico, línea de investigación o tutor para refinar los resultados.',
                side: 'right',
                align: 'start'
            }
        },
        {
            element: '#catalog-results-list',
            popover: {
                title: 'Resultados del Catálogo',
                description: 'Aquí verás las investigaciones que coinciden con los filtros. Haz clic en el título de cualquier obra para ver sus detalles completos, descargar el PDF y ver su ficha Dublin Core.',
                side: 'top',
                align: 'center'
            }
        }
    ];
};

const getMilestonesSteps = () => {
    return [
        {
            element: '#milestones-progress-card',
            popover: {
                title: 'Progreso de Tesis',
                description: 'Este widget circular te muestra el porcentaje acumulado de avance en base a los hitos que has cumplido.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '#milestones-timeline-card',
            popover: {
                title: 'Línea de Tiempo de Hitos',
                description: 'Aquí ves el listado vertical de entregas y defensas obligatorias. Puedes hacer clic en cualquiera para expandir los detalles, ver fechas de entrega y observaciones.',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: '#milestones-versions-card',
            popover: {
                title: 'Historial de Versiones',
                description: 'Lleva el control de todos los archivos PDF/Word cargados anteriormente. Puedes descargar versiones previas si lo necesitas.',
                side: 'left',
                align: 'center'
            }
        },
        {
            element: '#milestones-status-log',
            popover: {
                title: 'Bitácora de Estados',
                description: 'Un registro cronológico detallado de todos los cambios de estado en el flujo de aprobación de tu tesis, incluyendo quién fue el responsable y su comentario obligatorio.',
                side: 'top',
                align: 'center'
            }
        }
    ];
};

const getAssignedProductionsSteps = (role) => {
    const steps = [];
    if (role === 'Coordinador' || role === 'Decano') {
        if (document.querySelector('#directivo-tabs-switcher')) {
            steps.push({
                element: '#directivo-tabs-switcher',
                popover: {
                    title: 'Distribución de Carga Académica',
                    description: 'Cambia entre "Distribución de Tutores" y "Distribución de Jurados" para supervisar el estado de cada docente.',
                    side: 'bottom',
                    align: 'center'
                }
            });
        }
        if (document.querySelector('#tutor-search-input')) {
            steps.push({
                element: '#tutor-search-input',
                popover: {
                    title: 'Filtro de Profesores',
                    description: 'Escribe el nombre de cualquier docente para buscar y filtrar rápidamente sus tutorías o jurados asignados.',
                    side: 'bottom',
                    align: 'center'
                }
            });
        }
        if (document.querySelector('#tutors-list-container')) {
            steps.push({
                element: '#tutors-list-container',
                popover: {
                    title: 'Listado de Profesores',
                    description: 'Aquí verás a todos los docentes. Haz clic en el nombre de cualquiera para expandir el acordeón, ver sus trabajos y hacer clic en "Ver Tesis".',
                    side: 'top',
                    align: 'center'
                }
            });
        }
    } else if (role === 'Tutor' || role === 'Jurado') {
        if (document.querySelector('#docente-tabs-switcher')) {
            steps.push({
                element: '#docente-tabs-switcher',
                popover: {
                    title: 'Bandejas del Evaluador',
                    description: 'Alterna entre tus tutorías activas y las evaluaciones donde participas como jurado evaluador.',
                    side: 'bottom',
                    align: 'center'
                }
            });
        }
        if (document.querySelector('#docente-tutorias-container')) {
            steps.push({
                element: '#docente-tutorias-container',
                popover: {
                    title: 'Bandeja de Trabajos',
                    description: 'Lista de todas las investigaciones asignadas bajo tu supervisión. Haz clic en el botón "Evaluar" para ingresar al visor del PDF y registrar observaciones o veredictos.',
                    side: 'top',
                    align: 'center'
                }
            });
        }
    }
    return steps;
};

const getProductionsListSteps = () => {
    return [
        {
            element: '#btn-upload-new-production',
            popover: {
                title: 'Subir Nueva Producción',
                description: 'Haz clic aquí para cargar tu trabajo especial de grado, tesis o propuesta de investigación.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '#productions-list-container',
            popover: {
                title: 'Mis Producciones Científicas',
                description: 'Aquí verás todo el historial de tus trabajos, su estado actual y el botón de "Ver Detalles" para hacer correcciones o ver observaciones del tutor.',
                side: 'top',
                align: 'center'
            }
        }
    ];
};

const getBibliometricsSteps = () => {
    return [
        {
            element: '#bibliometrics-kpis',
            popover: {
                title: 'Métricas de Impacto',
                description: 'Consulta los indicadores globales como total de publicaciones aprobadas, visitas totales y descargas de PDFs.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '#bibliometrics-line-chart',
            popover: {
                title: 'Evolución de Publicaciones',
                description: 'Visualiza la evolución temporal de la producción científica a lo largo de los distintos periodos lectivos.',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: '#bibliometrics-comparisons',
            popover: {
                title: 'Rankings y Comparativas',
                description: 'Explora la producción por programa y línea de investigación, así como el ranking de productividad docente.',
                side: 'top',
                align: 'center'
            }
        }
    ];
};

const getProfileSteps = () => {
    return [
        {
            element: '#profile-tabs-navigator',
            popover: {
                title: 'Pestañas de Perfil',
                description: 'Alterna entre tus "Datos Personales", "Seguridad" (para cambiar tu contraseña) y tu "Ficha Académica" institucional.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '#profile-tab-content',
            popover: {
                title: 'Actualización de Perfil',
                description: 'Actualiza y gestiona tu información personal. Recuerda presionar "Guardar" para aplicar los cambios.',
                side: 'top',
                align: 'center'
            }
        }
    ];
};

const getRejectionsSteps = () => {
    return [
        {
            element: '#rejections-list-container',
            popover: {
                title: 'Propuestas de Rechazo Pendientes',
                description: 'Aquí verás el listado de tesis que los tutores o jurados han propuesto rechazar. Puedes examinar el motivo de rechazo y confirmar la decisión definitiva.',
                side: 'top',
                align: 'center'
            }
        }
    ];
};

export const getTourSteps = (role) => {
    const steps = [];
    const path = window.location.pathname;

    // ==========================================
    // 0. ASSIGNED PRODUCTIONS (Tutor, Jurado, Coordinador, Decano)
    // ==========================================
    if (path.includes('/assigned-productions')) {
        return getAssignedProductionsSteps(role);
    }

    // ==========================================
    // 0.1. PRODUCTIONS LIST (Mis Producciones)
    // ==========================================
    if (path.endsWith('/productions') || path.endsWith('/productions/')) {
        return getProductionsListSteps();
    }

    // ==========================================
    // 0.2. BIBLIOMETRICS
    // ==========================================
    if (path.includes('/bibliometrics')) {
        return getBibliometricsSteps();
    }

    // ==========================================
    // 0.3. PROFILE
    // ==========================================
    if (path.includes('/profile')) {
        return getProfileSteps();
    }

    // ==========================================
    // 0.4. REJECTIONS
    // ==========================================
    if (path.includes('/propuestas-rechazo')) {
        return getRejectionsSteps();
    }

    // ==========================================
    // 1. CATALOG PAGE (All Roles)
    // ==========================================
    if (path.includes('/catalog')) {
        return getCatalogSteps();
    }

    // ==========================================
    // 2. PROGRESS / MILESTONES (All Roles)
    // ==========================================
    if (path.includes('/progress/student') || path.includes('/progreso') || path.includes('/mis-hitos')) {
        return getMilestonesSteps();
    }

    // ==========================================
    // 3. DASHBOARD PAGE (Role-Specific)
    // ==========================================
    if (path.endsWith('/dashboard') || path === '/' || path.endsWith('/home')) {
        if (role === 'Estudiante') {
            if (document.querySelector('#sidebar-container')) {
                steps.push({
                    element: '#sidebar-container',
                    popover: {
                        title: 'Cabina de Control',
                        description: 'Este es el menú lateral donde puedes navegar por las diferentes secciones del sistema, ver tus entregas e hitos.',
                        side: 'right',
                        align: 'start'
                    }
                });
            }
            if (document.querySelector('#active-production-card')) {
                steps.push({
                    element: '#active-production-card',
                    popover: {
                        title: 'Tu Trabajo Especial',
                        description: 'Aquí se muestra tu tesis de grado actual con su estado (Borrador, En Revisión, etc.) y las observaciones del tutor.',
                        side: 'bottom',
                        align: 'center'
                    }
                });
            }
            if (document.querySelector('#student-progress-card')) {
                steps.push({
                    element: '#student-progress-card',
                    popover: {
                        title: 'Avance Académico',
                        description: 'Este widget circular te indica el porcentaje de avance general de tu investigación y las observaciones pendientes.',
                        side: 'left',
                        align: 'center'
                    }
                });
            }
            if (document.querySelector('#student-observations-inbox')) {
                steps.push({
                    element: '#student-observations-inbox',
                    popover: {
                        title: 'Buzón de Observaciones',
                        description: 'Aquí verás las revisiones y correcciones detalladas del tutor. Puedes hacer clic en los comentarios para ver el detalle y responder.',
                        side: 'top',
                        align: 'center'
                    }
                });
            }
            const btnNew = document.querySelector('[href*="productions/create"]') || document.querySelector('#btn-new-production');
            if (btnNew) {
                steps.push({
                    element: btnNew,
                    popover: {
                        title: 'Cargar Nuevo Trabajo',
                        description: 'Haz clic aquí para subir una nueva tesis o propuesta. Te redirigiremos al formulario.',
                        side: 'top',
                        align: 'center'
                    },
                    onNextClick: () => {
                        localStorage.setItem('skms_active_tour', 'Estudiante');
                        localStorage.setItem('skms_tour_step', '0'); 
                        localStorage.setItem('skms_transitioning', 'true');
                        btnNew.click();
                    }
                });
            }
        } else if (role === 'Tutor') {
            if (document.querySelector('#sidebar-container')) {
                steps.push({
                    element: '#sidebar-container',
                    popover: {
                        title: 'Menú de Tutor',
                        description: 'Accede a tus opciones, incluyendo "Trabajos Asignados" para revisar las tesis de tus estudiantes.',
                        side: 'right',
                        align: 'start'
                    }
                });
            }
            const assignedTable = document.querySelector('#tutor-assigned-table') || document.querySelector('.assigned-productions-table');
            if (assignedTable) {
                steps.push({
                    element: assignedTable,
                    popover: {
                        title: 'Tesis Asignadas',
                        description: 'Aquí verás un listado de todas las investigaciones donde estás registrado como tutor académico.',
                        side: 'bottom',
                        align: 'center'
                    }
                });
            }
            const btnEvaluate = document.querySelector('.btn-evaluate-production') || document.querySelector('[href*="/workflow/revisions"]');
            if (btnEvaluate) {
                steps.push({
                    element: btnEvaluate,
                    popover: {
                        title: 'Revisar Trabajo',
                        description: 'Haz clic aquí para ingresar a la pantalla de corrección metodológica del manuscrito. Te redirigiremos.',
                        side: 'left',
                        align: 'center'
                    },
                    onNextClick: () => {
                        localStorage.setItem('skms_active_tour', 'Tutor');
                        localStorage.setItem('skms_tour_step', '0');
                        localStorage.setItem('skms_transitioning', 'true');
                        btnEvaluate.click();
                    }
                });
            }
        } else if (role === 'Jurado') {
            if (document.querySelector('#role-switcher')) {
                steps.push({
                    element: '#role-switcher',
                    popover: {
                        title: 'Perfil Activo',
                        description: 'Si tienes múltiples perfiles (ej. Tutor y Jurado), puedes alternar entre ellos usando este selector en la barra superior.',
                        side: 'bottom',
                        align: 'center'
                    }
                });
            }
            const jurorTable = document.querySelector('#juror-assigned-table') || document.querySelector('.assigned-productions-table');
            if (jurorTable) {
                steps.push({
                    element: jurorTable,
                    popover: {
                        title: 'Evaluaciones del Jurado',
                        description: 'Listado de tesis donde has sido designado como jurado evaluador para emitir un veredicto definitivo.',
                        side: 'bottom',
                        align: 'center'
                    }
                });
            }
            const btnJuryEvaluate = document.querySelector('.btn-evaluate-production') || document.querySelector('[href*="/workflow/revisions"]');
            if (btnJuryEvaluate) {
                steps.push({
                    element: btnJuryEvaluate,
                    popover: {
                        title: 'Veredicto del Jurado',
                        description: 'Ingresa a la pantalla de evaluación para revisar el PDF del trabajo y emitir tu calificación/voto.',
                        side: 'left',
                        align: 'center'
                    },
                    onNextClick: () => {
                        localStorage.setItem('skms_active_tour', 'Jurado');
                        localStorage.setItem('skms_tour_step', '0');
                        localStorage.setItem('skms_transitioning', 'true');
                        btnJuryEvaluate.click();
                    }
                });
            }
        } else if (role === 'Coordinador' || role === 'Decano') {
            if (document.querySelector('#coordinator-stats')) {
                steps.push({
                    element: '#coordinator-stats',
                    popover: {
                        title: 'Métricas del Decanato',
                        description: 'Visualiza en tiempo real estadísticas globales como el total de tesis cargadas, en revisión, aprobadas y rechazadas.',
                        side: 'bottom',
                        align: 'center'
                    }
                });
            }
            if (document.querySelector('#students-tracking-table')) {
                steps.push({
                    element: '#students-tracking-table',
                    popover: {
                        title: 'Monitoreo de Estudiantes',
                        description: 'Listado completo de tesistas activos. Permite supervisar su porcentaje de avance, tutor asignado y días restantes para el vencimiento.',
                        side: 'top',
                        align: 'center'
                    }
                });
            }
            const btnImport = document.querySelector('[href*="productions/import"]') || document.querySelector('#btn-import-menu');
            if (btnImport) {
                steps.push({
                    element: btnImport,
                    popover: {
                        title: 'Importación Histórica',
                        description: 'Haz clic aquí para ir al importador inteligente con IA para indexar tesis de años anteriores.',
                        side: 'left',
                        align: 'center'
                    },
                    onNextClick: () => {
                        localStorage.setItem('skms_active_tour', role);
                        localStorage.setItem('skms_tour_step', '0');
                        localStorage.setItem('skms_transitioning', 'true');
                        btnImport.click();
                    }
                });
            }
        } else if (role === 'Super Admin') {
            if (document.querySelector('#admin-dashboard-kpis')) {
                steps.push({
                    element: '#admin-dashboard-kpis',
                    popover: {
                        title: 'Indicadores Globales',
                        description: 'Resumen rápido de la cantidad total de usuarios registrados y el estado activo del servidor de interoperabilidad OAI-PMH.',
                        side: 'bottom',
                        align: 'center'
                    }
                });
            }
            if (document.querySelector('#admin-dashboard-audit-card')) {
                steps.push({
                    element: '#admin-dashboard-audit-card',
                    popover: {
                        title: 'Logs de Auditoría en Tiempo Real',
                        description: 'Monitorea las 10 últimas acciones realizadas en la plataforma por cualquier investigador, tutor o jurado.',
                        side: 'top',
                        align: 'center'
                    }
                });
            }
            if (document.querySelector('#admin-dashboard-maintenance-card')) {
                steps.push({
                    element: '#admin-dashboard-maintenance-card',
                    popover: {
                        title: 'Mantenimiento y Servidor',
                        description: 'Acciones del sistema para realizar respaldos MySQL en caliente del servidor o verificar el endpoint de interoperabilidad XML.',
                        side: 'left',
                        align: 'center'
                    }
                });
            }
        }
        return steps;
    }

    // ==========================================
    // 4. PRODUCTION DETAIL VIEW (Role-Specific)
    // ==========================================
    if (path.includes('/productions/') && !path.includes('/create') && !path.includes('/import') && !path.includes('/progreso')) {
        // PDF Viewer (All Roles can view it if active)
        if (document.querySelector('#pdf-main-container')) {
            steps.push({
                element: '#pdf-main-container',
                popover: {
                    title: 'Visor de PDF Integrado',
                    description: 'Lee y visualiza el manuscrito académico de forma interactiva.',
                    side: 'right',
                    align: 'center'
                }
            });
        }

        // Role-Specific steps inside Show Detail
        if (role === 'Estudiante') {
            if (document.querySelector('#observations-panel')) {
                steps.push({
                    element: '#observations-panel',
                    popover: {
                        title: 'Observaciones y Comentarios',
                        description: 'Aquí ves las correcciones metodológicas detalladas que tu tutor o jurado te han dejado. Responde a sus dudas directamente.',
                        side: 'left',
                        align: 'center'
                    }
                });
            }
            if (document.querySelector('#new-pdf-resubmit')) {
                steps.push({
                    element: '#new-pdf-resubmit',
                    popover: {
                        title: 'Subir Nueva Versión',
                        description: 'Cuando hayas corregido el trabajo, puedes subir un nuevo archivo PDF aquí para continuar el flujo de revisión.',
                        side: 'top',
                        align: 'center'
                    }
                });
            }
        } else if (role === 'Tutor' || role === 'Jurado') {
            if (document.querySelector('#observations-panel')) {
                steps.push({
                    element: '#observations-panel',
                    popover: {
                        title: 'Añadir Observaciones',
                        description: 'Crea comentarios vinculados a páginas y secciones exactas para indicarle las correcciones metodológicas que el tesista debe realizar.',
                        side: 'left',
                        align: 'center'
                    }
                });
            }
            if (document.querySelector('#decision-actions-panel')) {
                steps.push({
                    element: '#decision-actions-panel',
                    popover: {
                        title: 'Evaluación y Veredicto',
                        description: role === 'Tutor' 
                            ? 'Aprueba la entrega, solicita correcciones o rechaza el manuscrito para que el estudiante pueda continuar.'
                            : 'Emite el voto definitivo de aprobación para habilitar la publicación de la tesis.',
                        side: 'top',
                        align: 'center'
                    }
                });
            }
        } else if (role === 'Coordinador' || role === 'Decano' || role === 'Super Admin') {
            // Coordinator can assign tutor/jury and approve
            const userAssignCard = document.querySelector('[action*="assign-users"]') || document.querySelector('#tutor_id');
            if (userAssignCard) {
                steps.push({
                    element: userAssignCard,
                    popover: {
                        title: 'Designación de Evaluadores',
                        description: 'Como Coordinación, puedes asignar o reasignar el Tutor Metodológico y los Jurados de Tesis para este trabajo científico.',
                        side: 'left',
                        align: 'center'
                    }
                });
            }
            if (document.querySelector('#decision-actions-panel')) {
                steps.push({
                    element: '#decision-actions-panel',
                    popover: {
                        title: 'Publicar e Indexar',
                        description: 'Aprueba el paso final para que la obra quede oficialmente PUBLICADA, sea indexada en el catálogo y esté disponible vía OAI-PMH.',
                        side: 'top',
                        align: 'center'
                    }
                });
            }
        }
        return steps;
    }

    // ==========================================
    // 5. PRODUCTION CREATE PAGE
    // ==========================================
    if (path.includes('/productions/create')) {
        if (document.querySelector('#tab-google-drive')) {
            steps.push({
                element: '#tab-google-drive',
                popover: {
                    title: 'Google Drive Integrado',
                    description: 'Además de archivos locales, te recomendamos usar la pestaña Google Drive para vincular tus Google Docs académicos.',
                    side: 'bottom',
                    align: 'center'
                }
            });
        }
        if (document.querySelector('#btn-google-drive-search')) {
            steps.push({
                element: '#btn-google-drive-search',
                popover: {
                    title: 'Buscar en tu Nube',
                    description: 'Haz clic aquí para iniciar sesión en tu cuenta de Google y elegir tu documento. El sistema sincronizará los comentarios automáticamente.',
                    side: 'top',
                    align: 'center'
                }
            });
        }
        if (document.querySelector('#production-upload-form')) {
            steps.push({
                element: '#production-upload-form',
                popover: {
                    title: 'Formulario de Tesis',
                    description: 'Ingresa los metadatos Dublin Core de tu investigación y sube el archivo PDF o Word para que la IA los procese.',
                    side: 'top',
                    align: 'center'
                }
            });
        }
        return steps;
    }

    // ==========================================
    // 6. ADMIN & COORDINATION MODULES
    // ==========================================
    // 6.1 Importador Masivo
    if (path.includes('/productions/import')) {
        if (document.querySelector('#import-defaults-card')) {
            steps.push({
                element: '#import-defaults-card',
                popover: {
                    title: 'Valores Predeterminados',
                    description: 'Ahorra tiempo predefiniendo el período académico, carrera y tipo de trabajo aplicable a todo el lote de archivos.',
                    side: 'bottom',
                    align: 'center'
                }
            });
        }
        if (document.querySelector('#import-dropzone')) {
            steps.push({
                element: '#import-dropzone',
                popover: {
                    title: 'Zona de Carga Masiva',
                    description: 'Arrastra y suelta los documentos PDF o Word. El motor del sistema procesará y extraerá los metadatos Dublin Core con Inteligencia Artificial.',
                    side: 'top',
                    align: 'center'
                }
            });
        }
        return steps;
    }

    // 6.2 Configuración Académica (Programas, Líneas, Períodos)
    if (path.includes('/admin/programs') || path.includes('/admin/lines') || path.includes('/admin/periods')) {
        if (document.querySelector('#admin-config-tabs')) {
            steps.push({
                element: '#admin-config-tabs',
                popover: {
                    title: 'Centro de Configuración',
                    description: 'Navega rápidamente entre la administración de Programas Académicos, Líneas de Investigación y Períodos Lectivos.',
                    side: 'bottom',
                    align: 'center'
                }
            });
        }
        if (document.querySelector('#admin-config-table-card')) {
            steps.push({
                element: '#admin-config-table-card',
                popover: {
                    title: 'Tabla de Parámetros',
                    description: 'Crea, edita o deshabilita los registros institucionales fundamentales para la catalogación.',
                    side: 'top',
                    align: 'center'
                }
            });
        }
        return steps;
    }

    // 6.3 Usuarios
    if (path.includes('/admin/users')) {
        if (document.querySelector('#admin-users-table-card')) {
            steps.push({
                element: '#admin-users-table-card',
                popover: {
                    title: 'Control de Usuarios',
                    description: 'Listado global de todos los usuarios registrados (Estudiantes, Tutores, Jurados y Coordinadores).',
                    side: 'bottom',
                    align: 'center'
                }
            });
        }
        if (document.querySelector('#admin-users-drawer-card')) {
            steps.push({
                element: '#admin-users-drawer-card',
                popover: {
                    title: 'Asignación de Roles',
                    description: 'Este panel lateral se despliega al editar un usuario, permitiéndote cambiar sus permisos institucionales al instante.',
                    side: 'left',
                    align: 'center'
                }
            });
        }
        return steps;
    }

    // 6.4 Jurados
    if (path.includes('/admin/juries')) {
        if (document.querySelector('#admin-juries-table-card')) {
            steps.push({
                element: '#admin-juries-table-card',
                popover: {
                    title: 'Designación de Jurados',
                    description: 'Gestiona la conformación de los comités evaluadores. Aquí puedes asignar o remover jurados a los trabajos de grado listos para defensa.',
                    side: 'top',
                    align: 'center'
                }
            });
        }
        return steps;
    }

    // 6.5 Aprobaciones
    if (path.includes('/admin/approvals')) {
        if (document.querySelector('#admin-approvals-table-card')) {
            steps.push({
                element: '#admin-approvals-table-card',
                popover: {
                    title: 'Bandeja de Aprobación',
                    description: 'Revisa y autoriza las tesis recomendadas por los tutores. Tu visto bueno es el paso final para que la obra pase al estado PUBLICADO.',
                    side: 'top',
                    align: 'center'
                }
            });
        }
        return steps;
    }

    // 6.6 Reclamaciones
    if (path.includes('/admin/claims')) {
        if (document.querySelector('#admin-claims-table-card')) {
            steps.push({
                element: '#admin-claims-table-card',
                popover: {
                    title: 'Reclamaciones de Autoría',
                    description: 'Evalúa y procesa solicitudes de estudiantes para reclamar y vincularse como autores a tesis históricas indexadas.',
                    side: 'top',
                    align: 'center'
                }
            });
        }
        return steps;
    }

    // 6.7 Reportes
    if (path.includes('/admin/reports')) {
        if (document.querySelector('#admin-reports-form-card')) {
            steps.push({
                element: '#admin-reports-form-card',
                popover: {
                    title: 'Generación de Reportes',
                    description: 'Filtra la productividad y exporta informes institucionales oficiales firmados en formatos PDF y Excel.',
                    side: 'top',
                    align: 'center'
                }
            });
        }
        return steps;
    }

    // 6.8 Auditoría / Logs
    if (path.includes('/admin/audit-logs')) {
        if (document.querySelector('#admin-audit-logs-table-card')) {
            steps.push({
                element: '#admin-audit-logs-table-card',
                popover: {
                    title: 'Bitácora Forense',
                    description: 'Registro histórico y detallado de todas las transacciones, cambios de estado y modificaciones de datos en el sistema.',
                    side: 'top',
                    align: 'center'
                }
            });
        }
        return steps;
    }

    return steps;
};
