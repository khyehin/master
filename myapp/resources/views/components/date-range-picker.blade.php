@props([
    'dateFrom' => '',
    'dateTo' => '',
    'dateAll' => '0',
])

@php
    $dateFrom = trim((string) $dateFrom);
    $dateTo = trim((string) $dateTo);
    $dateAll = trim((string) $dateAll) === '1' ? '1' : '0';
    $displayText = '';
    if ($dateAll === '1') {
        $displayText = __('All');
    } elseif ($dateFrom !== '' && $dateTo !== '') {
        $displayText = $dateFrom . ' to ' . $dateTo;
    } elseif ($dateFrom !== '') {
        $displayText = $dateFrom . ' to';
    } elseif ($dateTo !== '') {
        $displayText = 'to ' . $dateTo;
    }
@endphp

<div class="form-group overflow-visible"
     x-data="{
         open: false,
         dateFrom: @js($dateFrom),
         dateTo: @js($dateTo),
         dateAll: @js($dateAll),
         currentMonth: @js($dateFrom ? date('Y-m', strtotime($dateFrom)) : date('Y-m')),
         startDate: @js($dateFrom),
         endDate: @js($dateTo),
         dropdownStyle: '',
         get displayText() {
             if (this.dateAll === '1') return 'All';
             if (this.dateFrom && this.dateTo) return this.dateFrom + ' to ' + this.dateTo;
             if (this.dateFrom) return this.dateFrom + ' to';
             if (this.dateTo) return 'to ' + this.dateTo;
             return '';
         },
         get calendarDays() {
             return this.getCalendarDays();
         },
         openDropdown() {
             const trigger = this.$refs.trigger;
             if (trigger) {
                 const r = trigger.getBoundingClientRect();
                 this.dropdownStyle = `position: fixed; left: ${r.left}px; top: ${r.bottom + 6}px; min-width: 408px;`;
             }
             this.open = true;
             const self = this;
             this.$nextTick(() => {
                 requestAnimationFrame(() => {
                     const trig = self.$refs.trigger;
                     const panel = self.$refs.panel;
                     if (!trig || !panel) return;
                     const r = trig.getBoundingClientRect();
                     const pad = 8;
                     const w = Math.max(panel.offsetWidth, 408);
                     const h = Math.max(panel.offsetHeight, 280);
                     let left = r.left;
                     let top = r.bottom + 6;
                     if (left + w > window.innerWidth - pad) left = window.innerWidth - w - pad;
                     if (left < pad) left = pad;
                     if (top + h > window.innerHeight - pad) top = r.top - h - 6;
                     if (top < pad) top = pad;
                     self.dropdownStyle = `position: fixed; left: ${left}px; top: ${top}px; min-width: ${w}px;`;
                 });
             });
         },
         applyRange(range) {
             const now = new Date();
             let from = null, to = null;
             const fmt = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
             const getMon = (d) => { let day = d.getDay(); if (day === 0) day = 7; return new Date(d.getFullYear(), d.getMonth(), d.getDate() - day + 1); };
             if (range === 'all') {
                 this.dateFrom = ''; this.dateTo = ''; this.dateAll = '1'; this.startDate = ''; this.endDate = '';
                 this.currentMonth = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
                 return;
             }
             this.dateAll = '0';
             if (range === 'today') { from = to = new Date(now.getFullYear(), now.getMonth(), now.getDate()); }
             else if (range === 'yesterday') { const y = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1); from = to = y; }
             else if (range === 'this_week') { const mon = getMon(now); const sun = new Date(mon.getFullYear(), mon.getMonth(), mon.getDate() + 6); from = mon; to = sun; }
             else if (range === 'last_week') { const thisMon = getMon(now); const lastMon = new Date(thisMon.getFullYear(), thisMon.getMonth(), thisMon.getDate() - 7); const lastSun = new Date(lastMon.getFullYear(), lastMon.getMonth(), lastMon.getDate() + 6); from = lastMon; to = lastSun; }
             else if (range === 'this_month') { from = new Date(now.getFullYear(), now.getMonth(), 1); to = new Date(now.getFullYear(), now.getMonth() + 1, 0); }
             else if (range === 'last_month') { from = new Date(now.getFullYear(), now.getMonth() - 1, 1); to = new Date(now.getFullYear(), now.getMonth(), 0); }
             else if (range === 'this_year') { from = new Date(now.getFullYear(), 0, 1); to = new Date(now.getFullYear(), 11, 31); }
             else if (range === 'last_year') { const y = now.getFullYear() - 1; from = new Date(y, 0, 1); to = new Date(y, 11, 31); }
             if (from) { this.dateFrom = fmt(from); this.startDate = this.dateFrom; }
             if (to) { this.dateTo = fmt(to); this.endDate = this.dateTo; }
             this.currentMonth = from ? (from.getFullYear() + '-' + String(from.getMonth() + 1).padStart(2, '0')) : (now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
         },
         selectDay(dayObj) {
             if (!dayObj || dayObj.empty) return;
             this.dateAll = '0';
             const d = dayObj.date;
             const fmt = (x) => x.getFullYear() + '-' + String(x.getMonth() + 1).padStart(2, '0') + '-' + String(x.getDate()).padStart(2, '0');
             if (!this.startDate || (this.startDate && this.endDate)) {
                 this.startDate = fmt(d);
                 this.endDate = '';
                 this.dateFrom = this.startDate;
                 this.dateTo = '';
             } else {
                 if (d.getTime() < new Date(this.startDate).getTime()) {
                     this.endDate = this.startDate;
                     this.startDate = fmt(d);
                 } else {
                     this.endDate = fmt(d);
                 }
                 this.dateFrom = this.startDate;
                 this.dateTo = this.endDate;
             }
             this.currentMonth = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
         },
         prevMonth() {
             const [y, m] = this.currentMonth.split('-').map(Number);
             const d = new Date(y, m - 2, 1);
             this.currentMonth = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
         },
         nextMonth() {
             const [y, m] = this.currentMonth.split('-').map(Number);
             const d = new Date(y, m, 1);
             this.currentMonth = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
         },
         getCalendarDays() {
             const [y, m] = this.currentMonth.split('-').map(Number);
             const first = new Date(y, m - 1, 1);
             let wday = first.getDay();
             wday = (wday + 6) % 7;
             const daysInMonth = new Date(y, m, 0).getDate();
             const out = [];
             for (let i = 0; i < wday; i++) out.push({ empty: true });
             const start = this.startDate ? new Date(this.startDate) : null;
             const end = this.endDate ? new Date(this.endDate) : null;
             const same = (a, b) => a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
             const inRange = (d) => start && end && d.getTime() >= start.getTime() && d.getTime() <= end.getTime();
             for (let day = 1; day <= daysInMonth; day++) {
                 const date = new Date(y, m - 1, day);
                 out.push({ empty: false, day, date, isStart: start && same(date, start), isEnd: end && same(date, end), inRange: inRange(date) });
             }
             return out;
         }
     }"
     x-init="
         if (dateAll === '1') { startDate = ''; endDate = ''; dateFrom = ''; dateTo = ''; }
         if (startDate && endDate && new Date(endDate).getTime() < new Date(startDate).getTime()) { const t = startDate; startDate = endDate; endDate = t; dateFrom = startDate; dateTo = endDate; }
     ">
    <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Date Range') }}</label>
    <div class="date-filter-wrapper relative overflow-visible">
        <input type="text"
               x-ref="trigger"
               readonly
               :value="displayText"
               @click="openDropdown()"
               class="drp-display-input w-full rounded border border-gray-300 bg-white px-2.5 py-1.5 text-sm cursor-pointer focus:border-gray-400 focus:ring-1 focus:ring-gray-400 h-[34px]"
               placeholder="{{ __('Select date range') }}">
        <input type="hidden" name="date_from" :value="dateFrom">
        <input type="hidden" name="date_to" :value="dateTo">
        <input type="hidden" name="date_all" :value="dateAll">

        <template x-teleport="#drp-portal">
            <div x-show="open"
                 x-ref="panel"
                 x-cloak
                 x-transition
                 :style="dropdownStyle"
                 :class="{ 'drp-container': true, 'drp-container-open': open }"
                 @click.outside="open = false">
                <div class="drp-wrapper">
                <div class="drp-header">
                    <button type="button" @click.prevent="prevMonth()" class="drp-nav">&lt;</button>
                    <div class="drp-month-label" x-text="currentMonth"></div>
                    <button type="button" @click.prevent="nextMonth()" class="drp-nav">&gt;</button>
                </div>
                <div class="drp-week-row" style="display: flex; flex-direction: row; flex-wrap: nowrap;">
                    <span class="drp-week-cell">{{ __('Mo') }}</span><span class="drp-week-cell">{{ __('Tu') }}</span><span class="drp-week-cell">{{ __('We') }}</span><span class="drp-week-cell">{{ __('Th') }}</span><span class="drp-week-cell">{{ __('Fr') }}</span><span class="drp-week-cell">{{ __('Sa') }}</span><span class="drp-week-cell">{{ __('Su') }}</span>
                </div>
                <div class="drp-grid" style="display: grid; grid-template-columns: repeat(7, 1fr); grid-auto-flow: row;">
                    <template x-for="(cell, i) in calendarDays" :key="i">
                        <div :class="{
                                 'is-empty': cell.empty,
                                 'is-start': !cell.empty && cell.isStart,
                                 'is-end': !cell.empty && cell.isEnd,
                                 'is-in-range': !cell.empty && cell.inRange && !cell.isStart && !cell.isEnd
                             }"
                             @click="!cell.empty && selectDay(cell)"
                             x-text="cell.empty ? '' : cell.day"></div>
                    </template>
                </div>
            </div>
            <div class="drp-quick-bar">
                <button type="button" @click.prevent="applyRange('today')" class="drp-quick-item">{{ __('Today') }}</button>
                <button type="button" @click.prevent="applyRange('yesterday')" class="drp-quick-item">{{ __('Yesterday') }}</button>
                <button type="button" @click.prevent="applyRange('this_week')" class="drp-quick-item">{{ __('This week') }}</button>
                <button type="button" @click.prevent="applyRange('last_week')" class="drp-quick-item">{{ __('Last week') }}</button>
                <button type="button" @click.prevent="applyRange('this_month')" class="drp-quick-item">{{ __('This month') }}</button>
                <button type="button" @click.prevent="applyRange('last_month')" class="drp-quick-item">{{ __('Last month') }}</button>
                <button type="button" @click.prevent="applyRange('this_year')" class="drp-quick-item">{{ __('This year') }}</button>
                <button type="button" @click.prevent="applyRange('last_year')" class="drp-quick-item">{{ __('Last year') }}</button>
                <button type="button" @click.prevent="applyRange('all')" class="drp-quick-item">{{ __('All') }}</button>
            </div>
            </div>
        </template>
    </div>
</div>
