document.addEventListener('DOMContentLoaded', function () {
    const consultationInput = document.getElementById('consultation-input');

    if (!consultationInput) {
        return;
    }

    const stepCalendar = document.getElementById('booking-step-calendar');
    const modalOverlay = document.getElementById('booking-modal-overlay');
    const modalClose = document.getElementById('booking-modal-close');
    const stepForm = document.getElementById('booking-step-form');
    const stepVerify = document.getElementById('booking-step-verify');
    const stepError = document.getElementById('booking-step-error');
    const errorMessage = document.getElementById('booking-error-message');
    const calendarEl = document.getElementById('booking-calendar');
    const selectedSlotEl = document.getElementById('booking-selected-slot');
    const bookingForm = document.getElementById('booking-form');
    const verifyForm = document.getElementById('booking-verify-form');
    const successFlash = document.getElementById('booking-success-flash');
    const successFlashClose = document.getElementById('booking-success-flash-close');

    let calendar = null;
    let selectedSlot = null;
    let verificationId = null;

    function showError(message) {
        errorMessage.textContent = message;
        stepError.style.display = 'block';
    }

    function hideError() {
        stepError.style.display = 'none';
    }

    function openModal() {
        modalOverlay.style.display = 'flex';
    }

    function closeModal() {
        modalOverlay.style.display = 'none';
        stepForm.style.display = 'none';
        stepVerify.style.display = 'none';
        hideError();
        selectedSlot = null;
        verificationId = null;
        bookingForm.reset();
        verifyForm.reset();
    }

    function showSuccessFlash() {
        successFlash.style.display = 'flex';
    }

    function hideSuccessFlash() {
        successFlash.style.display = 'none';
    }

    modalClose.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', function (event) {
        if (event.target === modalOverlay) {
            closeModal();
        }
    });

    successFlashClose.addEventListener('click', hideSuccessFlash);
    successFlash.addEventListener('click', function (event) {
        if (event.target === successFlash) {
            hideSuccessFlash();
        }
    });

    // Retire le fuseau horaire d'une chaîne ISO fournie par FullCalendar
    // (ex: "2026-07-07T17:00:00+02:00" -> "2026-07-07T17:00:00")
    function stripTimezone(isoString) {
        return isoString.substring(0, 19);
    }

    function initCalendarForConsultation(consultationName) {
        hideSuccessFlash();
        closeModal();

        if (!consultationName) {
            stepCalendar.style.display = 'none';
            return;
        }

        stepCalendar.style.display = 'block';

        if (calendar) {
            calendar.destroy();
        }

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            locale: 'fr',
            firstDay: 1,
            allDayText: 'Journée',
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
                    + '?start=' + encodeURIComponent(stripTimezone(info.startStr))
                    + '&end=' + encodeURIComponent(stripTimezone(info.endStr));

                fetch(url)
                    .then(response => response.json())
                    .then(data => successCallback(data))
                    .catch(error => failureCallback(error));
            },
            eventClick: function (info) {
                selectedSlot = {
                    consultationName: consultationName,
                    start: stripTimezone(info.event.startStr),
                    end: stripTimezone(info.event.endStr),
                };

                const startDate = new Date(info.event.start);
                selectedSlotEl.textContent = 'Créneau choisi : ' + startDate.toLocaleString('fr-FR');

                stepForm.style.display = 'block';
                stepVerify.style.display = 'none';
                hideError();
                openModal();
            },
        });

        calendar.render();
    }

    consultationInput.addEventListener('change', function () {
        initCalendarForConsultation(consultationInput.value.trim());
    });

    // Initialisation immédiate si un type est déjà pré-rempli au chargement de la page.
    if (consultationInput.value.trim()) {
        initCalendarForConsultation(consultationInput.value.trim());
    }

    bookingForm.addEventListener('submit', function (event) {
        event.preventDefault();
        hideError();

        if (!selectedSlot) {
            showError('Merci de choisir un créneau.');
            return;
        }

        const formData = new FormData();
        formData.append('consultationName', selectedSlot.consultationName);
        formData.append('start', selectedSlot.start);
        formData.append('end', selectedSlot.end);
        formData.append('firstName', document.getElementById('booking-firstName').value);
        formData.append('lastName', document.getElementById('booking-lastName').value);
        formData.append('email', document.getElementById('booking-email').value);
        formData.append('phone', document.getElementById('booking-phone').value);
        formData.append('message', document.getElementById('booking-message').value);

        fetch('/rendez-vous/demarrer', {
            method: 'POST',
            body: formData,
        })
            .then(response => response.json().then(data => ({ status: response.status, data })))
            .then(({ status, data }) => {
                if (status !== 200 || !data.success) {
                    showError(data.error || 'Une erreur est survenue.');
                    return;
                }

                verificationId = data.verificationId;
                stepForm.style.display = 'none';
                stepVerify.style.display = 'block';
                if (calendar) {
                    calendar.refetchEvents();
                }
            })
            .catch(() => {
                showError('Une erreur est survenue lors de l\'envoi.');
            });
    });

    verifyForm.addEventListener('submit', function (event) {
        event.preventDefault();
        hideError();

        if (!verificationId) {
            showError('Une erreur est survenue, merci de recommencer.');
            return;
        }

        const formData = new FormData();
        formData.append('verificationId', verificationId);
        formData.append('code', document.getElementById('booking-code').value);

        fetch('/rendez-vous/verifier', {
            method: 'POST',
            body: formData,
        })
            .then(response => response.json().then(data => ({ status: response.status, data })))
            .then(({ status, data }) => {
                if (status !== 200 || !data.success) {
                    showError(data.error || 'Une erreur est survenue.');
                    return;
                }

                closeModal();
                showSuccessFlash();
                if (calendar) {
                    calendar.refetchEvents();
                }
            })
            .catch(() => {
                showError('Une erreur est survenue lors de la vérification.');
            });
    });
});