import AirDatepicker from 'air-datepicker';
import 'air-datepicker/air-datepicker.css';

const locale = {
    days: ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'],
    daysShort: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
    daysMin: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
    months: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
    monthsShort: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
    today: 'Hôm nay',
    clear: 'Xóa',
    dateFormat: 'dd/MM/yyyy',
    timeFormat: 'HH:mm',
    firstDay: 1,
};

function isoDate(date: Date): string {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function displayDate(date: Date): string {
    return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
}

function todayAtMidnight(): Date {
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth(), now.getDate());
}

function parseDate(value: string): Date {
    const parsed = new Date(`${value}T12:00:00`);
    return Number.isNaN(parsed.getTime()) ? todayAtMidnight() : parsed;
}

function initBirthDatepicker(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>('.date-picker').forEach((wrapper) => {
        if (wrapper.dataset.airDatepickerReady === 'true') return;

        const hidden = wrapper.querySelector<HTMLInputElement>('input[name="birth_date"]');
        const trigger = wrapper.querySelector<HTMLButtonElement>('.date-trigger');
        if (!hidden || !trigger) return;

        const today = todayAtMidnight();
        const value = hidden.value && !Number.isNaN(parseDate(hidden.value).getTime()) ? hidden.value : isoDate(today);
        hidden.value = value;

        const input = document.createElement('input');
        input.type = 'text';
        input.readOnly = true;
        input.className = 'date-trigger-air';
        input.value = displayDate(parseDate(value));
        input.setAttribute('aria-label', 'Ngày sinh');
        input.setAttribute('autocomplete', 'off');
        trigger.hidden = true;
        trigger.insertAdjacentElement('afterend', input);

        const datepicker = new AirDatepicker(input, {
            locale,
            dateFormat: 'dd/MM/yyyy',
            selectedDates: [parseDate(value)],
            minDate: new Date(1900, 0, 1),
            maxDate: today,
            autoClose: true,
            isMobile: false,
            buttons: ['today', 'clear'],
            onSelect: ({ date }) => {
                const selected = Array.isArray(date) ? date[0] : date;
                if (!selected) {
                    hidden.value = '';
                    input.value = '';
                } else {
                    hidden.value = isoDate(selected);
                    input.value = displayDate(selected);
                }
                hidden.dispatchEvent(new Event('input', { bubbles: true }));
                hidden.dispatchEvent(new Event('change', { bubbles: true }));
            },
        });

        input.addEventListener('click', () => datepicker.show());
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                datepicker.show();
            }
        });

        wrapper.dataset.airDatepickerReady = 'true';
        (wrapper as HTMLElement & { __techstoreDatepicker?: AirDatepicker }).__techstoreDatepicker = datepicker;
    });
}

export function initAirDatepicker(): void {
    if (typeof document === 'undefined') return;

    const run = () => initBirthDatepicker(document);
    run();
    requestAnimationFrame(run);
    window.setTimeout(run, 100);
    window.setTimeout(run, 500);

    if (document.body.dataset.techstoreAirDatepickerObserver === 'true') return;
    document.body.dataset.techstoreAirDatepickerObserver = 'true';

    const observer = new MutationObserver((mutations) => {
        if (mutations.some((mutation) => mutation.addedNodes.length > 0)) run();
    });

    observer.observe(document.body, { childList: true, subtree: true });
}
