document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('trece-event-modal');
    var titleElement = document.getElementById('trece-event-title');
    var startElement = document.getElementById('trece-event-start');
    var detailsElement = document.getElementById('trece-event-details');
    var idElement = document.getElementById('trece-event-id');
    var closeButton = document.getElementsByClassName('trece-close')[0];

    closeButton.onclick = function() {
        modal.style.display = 'none';
    };

    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };

    var calendarEl = document.getElementById('trece-agenda-calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
      eventClick: function(info) {
        idElement.value = info.event.id;
        titleElement.innerHTML = info.event.title;
        startElement.innerHTML = 'Fecha: ' + info.event.start.toLocaleDateString() + ' Hora: ' + info.event.start.toLocaleTimeString();
        detailsElement.innerHTML = 'Detalles: ' + info.event.extendedProps.detalles;
        modal.style.display = 'block';
      },
      locale: 'es',
      initialView: 'dayGridMonth',
      eventSources: [{
          url: '/wp-json/trece-agenda/v1/events',
          color: 'yellow', 
          textColor: 'black',
          success: function(response) {
            calendar.removeAllEventSources();
            var events = [];

            response.forEach(function(item) {
                events.push({
                    id: item.id,
                    title: '[' + item.servicio + '] ' + item.profesional,
                    start: item.fecha_hora,
                    extendedProps: {
                      detalles: item.detalles
                    },
                });
            });

            calendar.addEventSource(events);
          }
        }]
    });
    calendar.render();
  });