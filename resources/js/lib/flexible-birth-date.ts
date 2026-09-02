type CalendarState = { year: number; month: number };

const MONTHS = ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
const WEEKDAYS = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
const MIN_YEAR = -100_000_000_000;
const MAX_YEAR = 100_000_000_000;

function mod(value: number, divisor: number): number { return ((value % divisor) + divisor) % divisor; }
function isLeapYear(year: number): boolean { return mod(year, 4) === 0 && (mod(year, 100) !== 0 || mod(year, 400) === 0); }
function daysInMonth(year: number, month: number): number {
    if (month === 1) return isLeapYear(year) ? 29 : 28;
    return [3, 5, 8, 10].includes(month) ? 30 : 31;
}
function weekdayMondayFirst(year: number, month: number, day: number): number {
    let y = year;
    let m = month + 1;
    if (m < 3) { y -= 1; m += 12; }
    const k = mod(y, 100);
    const j = Math.floor(y / 100);
    const h = mod(day + Math.floor((13 * (m + 1)) / 5) + k + Math.floor(k / 4) + Math.floor(j / 4) + 5 * j, 7);
    return mod(h + 5, 7);
}
function padYear(year: number): string {
    const sign = year < 0 ? '-' : '';
    return `${sign}${String(Math.abs(year)).padStart(4, '0')}`;
}
function toIso(year: number, month: number, day: number): string { return `${padYear(year)}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`; }
function fromIso(value: string): { year: number; month: number; day: number } | null {
    const match = value.trim().match(/^(-?\d+)-(\d{1,2})-(\d{1,2})$/);
    if (!match) return null;
    const year = Number(match[1]);
    const month = Number(match[2]) - 1;
    const day = Number(match[3]);
    if (!Number.isSafeInteger(year) || year < MIN_YEAR || year > MAX_YEAR || month < 0 || month > 11 || day < 1 || day > daysInMonth(year, month)) return null;
    return { year, month, day };
}
function parseDisplay(value: string): { year: number; month: number; day: number } | null {
    const cleaned = value.trim().replace(/\s+/g, ' ');
    const match = cleaned.match(/^(\d{1,2})[\/-](\d{1,2})[\/-](-?\d+)(?:\s*(TCN|BCE))?$/i);
    if (!match) return fromIso(cleaned);
    const day = Number(match[1]);
    const month = Number(match[2]) - 1;
    let year = Number(match[3]);
    if (/^(TCN|BCE)$/i.test(match[4] ?? '')) year = -Math.abs(year);
    if (!Number.isSafeInteger(year) || year < MIN_YEAR || year > MAX_YEAR || month < 0 || month > 11 || day < 1 || day > daysInMonth(year, month)) return null;
    return { year, month, day };
}
function displayDate(year: number, month: number, day: number): string { return `${String(day).padStart(2, '0')}/${String(month + 1).padStart(2, '0')}/${Math.abs(year)}${year < 0 ? ' TCN' : ''}`; }
function currentDate(): { year: number; month: number; day: number } { const now = new Date(); return { year: now.getFullYear(), month: now.getMonth(), day: now.getDate() }; }
function clampYear(year: number): number { return Math.max(MIN_YEAR, Math.min(MAX_YEAR, Math.trunc(year))); }

function buildPicker(original: HTMLElement): void {
    if (original.dataset.flexibleBirthDate === '1') return;
    const hidden = original.querySelector<HTMLInputElement>('input[name="birth_date"]');
    if (!hidden) return;
    original.dataset.flexibleBirthDate = '1';
    original.style.display = 'none';

    const parsed = parseDisplay(hidden.value);
    const today = currentDate();
    const state: CalendarState = { year: parsed?.year ?? today.year, month: parsed?.month ?? today.month };
    let selected = parsed;
    const wrapper = document.createElement('div');
    wrapper.className = 'flexible-birth-picker';
    wrapper.innerHTML = `<div class="flexible-birth-input-row"><input class="flexible-birth-input" type="text" inputmode="numeric" autocomplete="bday" placeholder="dd/mm/yyyy" aria-label="Ngày sinh"><button class="flexible-birth-open" type="button" aria-label="Mở lịch"><span>▦</span></button></div><div class="flexible-birth-popover" hidden><div class="flexible-birth-head"><button class="flexible-birth-nav prev" type="button" aria-label="Tháng trước">‹</button><div class="flexible-birth-period"><select class="flexible-birth-month" aria-label="Chọn tháng"></select><input class="flexible-birth-year" type="number" inputmode="numeric" aria-label="Nhập năm" min="${MIN_YEAR}" max="${MAX_YEAR}" step="1"></div><button class="flexible-birth-nav next" type="button" aria-label="Tháng sau">›</button></div><div class="flexible-birth-week"></div><div class="flexible-birth-grid"></div><div class="flexible-birth-foot"><button class="flexible-birth-clear" type="button">Xóa</button><button class="flexible-birth-today" type="button">Hôm nay</button></div></div>`;

    const input = wrapper.querySelector<HTMLInputElement>('.flexible-birth-input')!;
    const openButton = wrapper.querySelector<HTMLButtonElement>('.flexible-birth-open')!;
    const popover = wrapper.querySelector<HTMLDivElement>('.flexible-birth-popover')!;
    const monthSelect = wrapper.querySelector<HTMLSelectElement>('.flexible-birth-month')!;
    const yearInput = wrapper.querySelector<HTMLInputElement>('.flexible-birth-year')!;
    const grid = wrapper.querySelector<HTMLDivElement>('.flexible-birth-grid')!;
    const week = wrapper.querySelector<HTMLDivElement>('.flexible-birth-week')!;
    MONTHS.forEach((name, index) => { const option = document.createElement('option'); option.value = String(index); option.textContent = name; monthSelect.appendChild(option); });
    WEEKDAYS.forEach((day) => { const span = document.createElement('span'); span.textContent = day; week.appendChild(span); });
    const syncHidden = (value: string) => { hidden.value = value; hidden.dispatchEvent(new Event('input', { bubbles: true })); hidden.dispatchEvent(new Event('change', { bubbles: true })); };
    const syncInput = () => { input.value = selected ? displayDate(selected.year, selected.month, selected.day) : ''; syncHidden(selected ? toIso(selected.year, selected.month, selected.day) : ''); };
    const render = () => {
        monthSelect.value = String(state.month); yearInput.value = String(state.year); grid.innerHTML = '';
        const offset = weekdayMondayFirst(state.year, state.month, 1);
        for (let index = 0; index < offset; index += 1) { const blank = document.createElement('span'); blank.className = 'flexible-birth-empty'; grid.appendChild(blank); }
        for (let day = 1; day <= daysInMonth(state.year, state.month); day += 1) {
            const button = document.createElement('button'); button.type = 'button'; button.textContent = String(day);
            if (selected && selected.year === state.year && selected.month === state.month && selected.day === day) button.classList.add('selected');
            button.addEventListener('click', () => { selected = { year: state.year, month: state.month, day }; syncInput(); popover.hidden = true; render(); input.focus(); });
            grid.appendChild(button);
        }
    };
    const open = () => { popover.hidden = false; render(); };
    syncInput();
    input.addEventListener('change', () => { const value = parseDisplay(input.value); if (!value) { syncInput(); return; } selected = value; state.year = value.year; state.month = value.month; syncInput(); render(); });
    input.addEventListener('keydown', (event) => { if (event.key === 'Enter') { event.preventDefault(); input.dispatchEvent(new Event('change')); open(); } if (event.key === 'Escape') popover.hidden = true; });
    openButton.addEventListener('click', () => { if (popover.hidden) open(); else popover.hidden = true; });
    monthSelect.addEventListener('change', () => { state.month = Number(monthSelect.value); render(); });
    yearInput.addEventListener('change', () => { state.year = clampYear(Number(yearInput.value) || 0); render(); });
    wrapper.querySelector<HTMLButtonElement>('.prev')!.addEventListener('click', () => { if (state.month === 0) { state.month = 11; state.year = clampYear(state.year - 1); } else state.month -= 1; render(); });
    wrapper.querySelector<HTMLButtonElement>('.next')!.addEventListener('click', () => { if (state.month === 11) { state.month = 0; state.year = clampYear(state.year + 1); } else state.month += 1; render(); });
    wrapper.querySelector<HTMLButtonElement>('.flexible-birth-clear')!.addEventListener('click', () => { selected = null; input.value = ''; syncHidden(''); popover.hidden = true; });
    wrapper.querySelector<HTMLButtonElement>('.flexible-birth-today')!.addEventListener('click', () => { selected = today; state.year = today.year; state.month = today.month; syncInput(); popover.hidden = true; render(); });
    document.addEventListener('click', (event) => { if (!wrapper.contains(event.target as Node)) popover.hidden = true; });
    original.parentElement?.insertBefore(wrapper, original.nextSibling);
}

export function initFlexibleBirthDatePicker(): void {
    if (typeof document === 'undefined') return;
    document.querySelectorAll<HTMLElement>('.date-picker').forEach(buildPicker);
    if (!document.body.dataset.flexibleBirthObserver) {
        document.body.dataset.flexibleBirthObserver = '1';
        const observer = new MutationObserver(() => document.querySelectorAll<HTMLElement>('.date-picker').forEach(buildPicker));
        observer.observe(document.body, { childList: true, subtree: true });
    }
}
