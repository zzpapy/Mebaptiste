import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

function initAdminCalendar() {
    const calendarEl = document.getElementById('admin-calendar');

    if (!calendarEl || calendarEl.dataset.bound === '1') {
        return;
    }
    calendarEl.dataset.bound = '1';

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
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
}

document.addEventListener('DOMContentLoaded', initAdminCalendar);
document.addEventListener('turbo:load', initAdminCalendar);