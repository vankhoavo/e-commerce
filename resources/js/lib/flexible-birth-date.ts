type CalendarDate = { year: number; month: number; day: number };

const MONTHS = ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
const MIN_YEAR = 1900;
const MAX_YEAR = new Date().getFullYear();

function daysInMonth(year: number, month: number): number {
    if (month === 1) return year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0) ? 29 : 28;
    return [3, 5, 8, 10].includes(month) ? 30 : 31;
}

function isValidDate(year: number, month: number, day: number): boolean {
    const today = new Date();
    if (!Number.isInteger(year) || year < MIN_YEAR || year > MAX_YEAR) return false;
    if (!Number.isInteger(month) || month < 0 || month > 11) return false;
    if (!Number.isInteger(day) || day < 1 || day > daysInMonth(year, month)) return false;
    if (year === today.getFullYear() && month > today.getMonth()) return false;
    if (year === today.getFullYear() && month === today.getMonth() && day > today.getDate()) return false;
    return true;
}

function parseDate(value: string): CalendarDate | null {
    const text = value.trim();
    if (!text) return null;
    let match = text.match(/^(\d{4,})-(\d{1,2})-(\d{1,2})$/);
    if (!match) match = text.match(/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4,})$/);
    if (!match) return null;
    const iso = /^\d{4,}-/.test(text);
    const year = Number(iso ? match[1] : match[3]);
    const month = Number(match[2]) - 1;
    const day = Number(iso ? match[3] : match[1]);
    return isValidDate(year, month, day) ? { year, month, day } : null;
}

function toIso(date: CalendarDate): string {
    return `${String(date.year).padStart(4, '0')}-${String(date.month + 1).padStart(2, '0')}-${String(date.day).padStart(2, '0')}`;
}

function toDisplay(date: CalendarDate | null): string {
    if (!date) return 'dd/mm/yyyy';
    return `${String(date.day).padStart(2, '0')}/${String(date.month + 1).padStart(2, '0')}/${date.year}`;
}

function createOption(value: string, label: string, selected = false): HTMLOptionElement {
    const option = document.createElement('option');
    option.value = value;
    option.textContent = label;
    option.selected = selected;
    return option;
}

let activeModal: HTMLElement | null = null;
let activeHidden: HTMLInputElement | null = null;
let activeSelected: CalendarDate | null = null;

function closeModal(): void {
    activeModal?.remove();
    activeModal = null;
    activeHidden = null;
    activeSelected = null;
    document.body.style.removeProperty('overflow');
}

function syncProfileField(hidden: HTMLInputElement, date: CalendarDate | null): void {
    const value = date ? toIso(date) : '';
    hidden.value = value;
    hidden.setAttribute('value', value);
    hidden.dataset.birthDateValue = value;
    const field = hidden.closest<HTMLElement>('.date-field');
    const label = field?.querySelector<HTMLElement>('.date-trigger span');
    if (label) {
        label.textContent = toDisplay(date);
        label.classList.toggle('placeholder', !date);
    }
    hidden.dispatchEvent(new Event('input', { bubbles: true }));
    hidden.dispatchEvent(new Event('change', { bubbles: true }));
}

function saveModal(day: HTMLSelectElement, month: HTMLSelectElement, year: HTMLSelectElement): void {
    const date: CalendarDate = { day: Number(day.value), month: Number(month.value), year: Number(year.value) };
    if (!activeHidden || !isValidDate(date.year, date.month, date.day)) return;
    syncProfileField(activeHidden, date);
    closeModal();
}

function openNativeSelect(select: HTMLSelectElement): void {
    select.focus();
    try {
        select.showPicker();
    } catch {
        select.click();
    }
}

function openModal(hidden: HTMLInputElement): void {
    closeModal();
    activeHidden = hidden;
    const storedValue = hidden.dataset.birthDateValue || hidden.value;
    activeSelected = parseDate(storedValue) ?? { year: 2001, month: 4, day: 4 };
    const today = new Date();
    if (!isValidDate(activeSelected.year, activeSelected.month, activeSelected.day)) {
        activeSelected = { year: Math.min(2001, today.getFullYear()), month: Math.min(4, today.getMonth()), day: 1 };
    }

    const overlay = document.createElement('div');
    overlay.className = 'birth-date-modal-overlay';
    overlay.innerHTML = `
        <div class="birth-date-modal" role="dialog" aria-modal="true" aria-labelledby="birth-date-title">
            <div class="birth-date-modal-header">
                <button type="button" class="birth-date-back" aria-label="Quay lại">‹</button>
                <h2 id="birth-date-title">Chỉnh sửa ngày sinh của bạn</h2>
                <button type="button" class="birth-date-close" aria-label="Đóng">×</button>
            </div>
            <p class="birth-date-description">Ngày sinh này sẽ được dùng cho các tài khoản và trang cá nhân trong Trung tâm tài khoản này. Bất kỳ chỉnh sửa nào mà bạn thay đổi đều sẽ áp dụng cho mọi trang cá nhân và tài khoản.</p>
            <div class="birth-date-selectors">
                <label class="birth-date-select"><span>Ngày</span><select class="birth-day" aria-label="Ngày"></select><button type="button" class="birth-date-chevron" aria-label="Mở danh sách ngày">⌄</button></label>
                <label class="birth-date-select"><span>Tháng</span><select class="birth-month" aria-label="Tháng"></select><button type="button" class="birth-date-chevron" aria-label="Mở danh sách tháng">⌄</button></label>
                <label class="birth-date-select"><span>Năm</span><select class="birth-year" aria-label="Năm"></select><button type="button" class="birth-date-chevron" aria-label="Mở danh sách năm">⌄</button></label>
            </div>
            <button type="button" class="birth-date-save">Lưu</button>
        </div>`;

    document.body.appendChild(overlay);
    activeModal = overlay;
    document.body.style.overflow = 'hidden';

    const day = overlay.querySelector<HTMLSelectElement>('.birth-day')!;
    const month = overlay.querySelector<HTMLSelectElement>('.birth-month')!;
    const year = overlay.querySelector<HTMLSelectElement>('.birth-year')!;

    for (let index = 0; index < 12; index += 1) month.appendChild(createOption(String(index), MONTHS[index], index === activeSelected.month));
    for (let value = 1; value <= 31; value += 1) day.appendChild(createOption(String(value), String(value), value === activeSelected.day));
    for (let value = MAX_YEAR; value >= MIN_YEAR; value -= 1) year.appendChild(createOption(String(value), String(value), value === activeSelected.year));

    const refreshDays = (): void => {
        const selectedDay = Number(day.value || activeSelected?.day || 1);
        const maximum = daysInMonth(Number(year.value), Number(month.value));
        const current = Math.min(selectedDay, maximum);
        day.innerHTML = '';
        for (let value = 1; value <= maximum; value += 1) day.appendChild(createOption(String(value), String(value), value === current));
    };

    refreshDays();
    year.addEventListener('change', refreshDays);
    month.addEventListener('change', refreshDays);
    overlay.querySelectorAll<HTMLButtonElement>('.birth-date-chevron').forEach((button, index) => {
        const selects = [day, month, year];
        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            openNativeSelect(selects[index]);
        });
    });
    overlay.querySelector<HTMLButtonElement>('.birth-date-save')!.addEventListener('click', () => saveModal(day, month, year));
    overlay.querySelector<HTMLButtonElement>('.birth-date-back')!.addEventListener('click', closeModal);
    overlay.querySelector<HTMLButtonElement>('.birth-date-close')!.addEventListener('click', closeModal);
    overlay.addEventListener('click', (event) => { if (event.target === overlay) closeModal(); });
}

function initBirthDateModal(): void {
    if (typeof document === 'undefined') return;
    const open = (event: Event): void => {
        const target = event.target as HTMLElement | null;
        const trigger = target?.closest<HTMLButtonElement>('.date-trigger');
        if (!trigger) return;
        const field = trigger.closest<HTMLElement>('.date-field');
        const hidden = field?.querySelector<HTMLInputElement>('input[name="birth_date"]');
        if (!hidden) return;
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        openModal(hidden);
    };
    document.removeEventListener('click', open, true);
    document.addEventListener('click', open, true);

    const submitGuard = (event: Event): void => {
        const form = event.target as HTMLFormElement | null;
        if (!form || form.tagName !== 'FORM') return;
        const hidden = form.querySelector<HTMLInputElement>('input[name="birth_date"]');
        if (!hidden) return;
        const value = hidden.dataset.birthDateValue || hidden.value;
        if (value) {
            const parsed = parseDate(value);
            if (!parsed) { event.preventDefault(); event.stopImmediatePropagation(); return; }
            hidden.value = toIso(parsed);
            hidden.setAttribute('value', hidden.value);
        } else {
            hidden.value = '';
            hidden.setAttribute('value', '');
        }
    };
    document.removeEventListener('submit', submitGuard, true);
    document.addEventListener('submit', submitGuard, true);
}

export function initFlexibleBirthDatePicker(): void {
    if (typeof document === 'undefined') return;
    initBirthDateModal();
}
