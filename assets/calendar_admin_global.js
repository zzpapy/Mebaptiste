document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('admin-calendar');

    if (!calendarEl) {
        return;
    }

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        locale: 'fr',
        firstDay: 1,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },
        slotMinTime: '08:00:00',
        slotMaxTime: '20:00:00',
        events: function (info, successCallback, failureCallback) {
            const url = '/admin/agenda/events'
                + '?start=' + encodeURIComponent(info.startStr)
                + '&end=' + encodeURIComponent(info.endStr);

            fetch(url)
                .then(response => response.json())
                .then(data => successCallback(data))
                .catch(error => failureCallback(error));
        },
    });

    calendar.render();
});