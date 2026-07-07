import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

function initBooking() {
    const consultationSelect = document.getElementById('consultation-select');

    if (!consultationSelect || consultationSelect.dataset.bound === '1') {
        return;
    }
    consultationSelect.dataset.bound = '1';

    const stepCalendar = document.getElementById('booking-step-calendar');
    const stepForm = document.getElementById('booking-step-form');
    const stepSuccess = document.getElementById('booking-step-success');
    const stepError = document.getElementById('booking-step-error');
    const errorMessage = document.getElementById('booking-error-message');
    const calendarEl = document.getElementById('booking-calendar');
    const selectedSlotEl = document.getElementById('booking-selected-slot');
    const bookingForm = document.getElementById('booking-form');

    let calendar = null;
    let selectedSlot = null;

    function showError(message) {
        errorMessage.textContent = message;
        stepError.style.display = 'block';
    }

    function hideError() {
        stepError.style.display = 'none';
    }

    consultationSelect.addEventListener('change', function () {
        const consultationId = consultationSelect.value;

        stepForm.style.display = 'none';
        stepSuccess.style.display = 'none';
        hideError();

        if (!consultationId) {
            stepCalendar.style.display = 'none';
            return;
        }

        stepCalendar.style.display = 'block';

        if (calendar) {
            calendar.destroy();
        }

        calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
            initialView: 'timeGridWeek',
            locale: 'fr',
            firstDay: 1,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'timeGridWeek,timeGridDay',
            },
            slotMinTime: '08:00:00',
            slotMaxTime: '20:00:00',
            selectable: false,
            events: function (info, successCallback, failureCallback) {
                const url = '/rendez-vous/creneaux'
                    + '?consultationId=' + encodeURIComponent(consultationId)
                    + '&start=' + encodeURIComponent(info.startStr)
                    + '&end=' + encodeURIComponent(info.endStr);

                fetch(url)
                    .then(response => response.json())
                    .then(data => successCallback(data))
                    .catch(error => failureCallback(error));
            },
            eventClick: function (info) {
                selectedSlot = {
                    consultationId: consultationId,
                    start: info.event.startStr,
                    end: info.event.endStr,
                };

                const startDate = new Date(info.event.start);
                selectedSlotEl.textContent = 'Créneau choisi : ' + startDate.toLocaleString('fr-FR');

                stepForm.style.display = 'block';
                hideError();
            },
        });

        calendar.render();
    });

    bookingForm.addEventListener('submit', function (event) {
        event.preventDefault();
        hideError();

        if (!selectedSlot) {
            showError('Merci de choisir un créneau.');
            return;
        }

        const formData = new FormData();
        formData.append('consultationId', selectedSlot.consultationId);
        formData.append('start', selectedSlot.start);
        formData.append('end', selectedSlot.end);
        formData.append('firstName', document.getElementById('booking-firstName').value);
        formData.append('lastName', document.getElementById('booking-lastName').value);
        formData.append('email', document.getElementById('booking-email').value);
        formData.append('phone', document.getElementById('booking-phone').value);
        formData.append('message', document.getElementById('booking-message').value);

        fetch('/rendez-vous/confirmer', {
            method: 'POST',
            body: formData,
        })
            .then(response => response.json().then(data => ({ status: response.status, data })))
            .then(({ status, data }) => {
                if (status !== 200 || !data.success) {
                    showError(data.error || 'Une erreur est survenue.');
                    return;
                }

                stepForm.style.display = 'none';
                stepSuccess.style.display = 'block';
                if (calendar) {
                    calendar.refetchEvents();
                }
            })
            .catch(() => {
                showError('Une erreur est survenue lors de l\'envoi.');
            });
    });
}

document.addEventListener('DOMContentLoaded', initBooking);
document.addEventListener('turbo:load', initBooking);