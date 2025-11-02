<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

if(!isLoggedIn()) {
    redirect('login.php');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos - Mi Red Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-3">
                <?php include '../includes/sidebar.php'; ?>
            </div>
            
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Eventos</h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
                        <i class="fas fa-plus"></i> Crear Evento
                    </button>
                </div>

                <!-- Próximos Eventos -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Próximos Eventos</h5>
                    </div>
                    <div class="card-body">
                        <div id="upcomingEvents">
                            <!-- Los eventos se cargarán aquí -->
                        </div>
                    </div>
                </div>

                <!-- Calendario -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Calendario</h5>
                    </div>
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Evento -->
    <div class="modal fade" id="createEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nuevo Evento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createEventForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="eventTitle" class="form-label">Título del Evento</label>
                                    <input type="text" class="form-control" id="eventTitle" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="eventLocation" class="form-label">Ubicación</label>
                                    <input type="text" class="form-control" id="eventLocation" name="location">
                                </div>
                                <div class="mb-3">
                                    <label for="eventStart" class="form-label">Fecha y Hora de Inicio</label>
                                    <input type="datetime-local" class="form-control" id="eventStart" name="start_date" required>
                                </div>
                                <div class="mb-3">
                                    <label for="eventEnd" class="form-label">Fecha y Hora de Fin (Opcional)</label>
                                    <input type="datetime-local" class="form-control" id="eventEnd" name="end_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="eventDescription" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="eventDescription" name="description" rows="4"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="eventCover" class="form-label">Imagen del Evento</label>
                                    <input type="file" class="form-control" id="eventCover" name="cover_image" accept="image/*">
                                </div>
                                <div class="mb-3">
                                    <label for="eventMaxAttendees" class="form-label">Límite de Asistentes</label>
                                    <input type="number" class="form-control" id="eventMaxAttendees" name="max_attendees" min="0">
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="eventPublic" name="is_public" checked>
                                    <label class="form-check-label" for="eventPublic">
                                        Evento Público
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Evento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        loadUpcomingEvents();
        
        document.getElementById('createEventForm').addEventListener('submit', function(e) {
            e.preventDefault();
            createEvent();
        });
    });

    function loadUpcomingEvents() {
        fetch('../api/events.php?action=get_events')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                renderUpcomingEvents(data.events);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function renderUpcomingEvents(events) {
        const container = document.getElementById('upcomingEvents');
        if(events.length === 0) {
            container.innerHTML = '<p class="text-muted">No hay eventos próximos.</p>';
            return;
        }

        container.innerHTML = events.map(event => {
            const startDate = new Date(event.start_date);
            const endDate = event.end_date ? new Date(event.end_date) : null;
            
            return `
                <div class="card event-card mb-3">
                    <div class="row g-0">
                        <div class="col-md-3">
                            <div class="event-date h-100 d-flex flex-column justify-content-center text-white text-center p-3" 
                                 style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                <div class="day">${startDate.getDate()}</div>
                                <div class="month">${startDate.toLocaleString('es', { month: 'short' })}</div>
                                <div class="year">${startDate.getFullYear()}</div>
                                <small>${startDate.toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' })}</small>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="card-body">
                                <h5 class="card-title">${event.title}</h5>
                                <p class="card-text">${event.description || 'Sin descripción'}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt"></i> ${event.location || 'Ubicación no especificada'}
                                    </small>
                                    <div>
                                        <span class="badge bg-success me-2">${event.going_count} asistentes</span>
                                        <span class="badge bg-warning">${event.interested_count} interesados</span>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    ${getRSVPButtons(event)}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function getRSVPButtons(event) {
        if(event.user_status === 'going') {
            return `
                <button class="btn btn-success btn-sm me-2" disabled>
                    <i class="fas fa-check"></i> Asistiré
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="updateRSVP(${event.id}, 'interested')">
                    Me interesa
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="updateRSVP(${event.id}, 'not_going')">
                    No asistiré
                </button>
            `;
        } else if(event.user_status === 'interested') {
            return `
                <button class="btn btn-outline-success btn-sm me-2" onclick="updateRSVP(${event.id}, 'going')">
                    Asistiré
                </button>
                <button class="btn btn-warning btn-sm me-2" disabled>
                    <i class="fas fa-star"></i> Me interesa
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="updateRSVP(${event.id}, 'not_going')">
                    No asistiré
                </button>
            `;
        } else {
            return `
                <button class="btn btn-outline-success btn-sm me-2" onclick="updateRSVP(${event.id}, 'going')">
                    Asistiré
                </button>
                <button class="btn btn-outline-warning btn-sm me-2" onclick="updateRSVP(${event.id}, 'interested')">
                    Me interesa
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="updateRSVP(${event.id}, 'not_going')">
                    No asistiré
                </button>
            `;
        }
    }

    function createEvent() {
        const formData = new FormData(document.getElementById('createEventForm'));
        formData.append('action', 'create_event');

        fetch('../api/events.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                $('#createEventModal').modal('hide');
                document.getElementById('createEventForm').reset();
                loadUpcomingEvents();
            } else {
                alert('Error al crear evento: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al crear evento');
        });
    }

    function updateRSVP(eventId, status) {
        fetch('../api/events.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'rsvp_event',
                event_id: eventId,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                loadUpcomingEvents();
            } else {
                alert('Error al actualizar asistencia');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al actualizar asistencia');
        });
    }
    </script>
</body>
</html>