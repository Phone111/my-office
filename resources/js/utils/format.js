// ===== ตัวช่วยจัดรูปแบบกลาง (ใช้ร่วมทุกหน้า) =====

const TH_MONTHS_SHORT = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

/**
 * แปลงวันที่เป็นรูปแบบไทย (พ.ศ.) — เช่น "12 มิ.ย. 2569" หรือ "12 มิ.ย. 2569 08:30 น."
 * รับได้ทั้ง Date object, ISO string, หรือ "YYYY-MM-DD HH:mm:ss"
 */
export function thaiDate(value, { time = false } = {}) {
    if (!value) return '—';
    const d = value instanceof Date ? value : new Date(String(value).replace(' ', 'T'));
    if (isNaN(d.getTime())) return String(value);

    const base = `${d.getDate()} ${TH_MONTHS_SHORT[d.getMonth()]} ${d.getFullYear() + 543}`;
    if (!time) return base;

    const hh = String(d.getHours()).padStart(2, '0');
    const mm = String(d.getMinutes()).padStart(2, '0');
    return `${base} ${hh}:${mm} น.`;
}

/** จำนวนเงินบาท เช่น 1,234.50 */
export function baht(n) {
    return Number(n || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/** จำนวนเต็มพร้อมคอมมา เช่น 1,234 */
export function num(n) {
    return Number(n || 0).toLocaleString('th-TH');
}
