document.addEventListener('DOMContentLoaded', function () {
    const input = document.querySelector('input[name$="[consultationName]"]');

    if (!input) {
        return;
    }

    fetch('/admin/consultations.json')
        .then(response => response.json())
        .then(names => {
            const datalist = document.createElement('datalist');
            datalist.id = 'consultation-datalist-admin';

            names.forEach(name => {
                const option = document.createElement('option');
                option.value = name;
                datalist.appendChild(option);
            });

            input.setAttribute('list', 'consultation-datalist-admin');
            input.setAttribute('autocomplete', 'off');
            input.after(datalist);
        })
        .catch(() => {
            // Si la liste échoue à charger, le champ texte reste utilisable normalement.
        });
});