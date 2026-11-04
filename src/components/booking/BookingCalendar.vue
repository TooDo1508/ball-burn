<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({ date: { type: String, required: true }, today: { type: String, required: true }, maxDate: { type: String, required: true } });
const emit = defineEmits(['select']);
const weekdayLabels = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
const visibleMonth = ref(monthStart(props.date));

watch(() => props.date, (value) => { visibleMonth.value = monthStart(value); });

function parseDate(value) {
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day, 12);
}

function dateKey(value) {
    return [value.getFullYear(), String(value.getMonth() + 1).padStart(2, '0'), String(value.getDate()).padStart(2, '0')].join('-');
}

function monthStart(value) {
    const date = parseDate(value);
    return new Date(date.getFullYear(), date.getMonth(), 1, 12);
}

const monthTitle = computed(() => new Intl.DateTimeFormat('vi-VN', { month: 'long', year: 'numeric' }).format(visibleMonth.value));
const minMonth = computed(() => monthStart(props.today).getTime());
const maxMonth = computed(() => monthStart(props.maxDate).getTime());
const canGoPrevious = computed(() => visibleMonth.value.getTime() > minMonth.value);
const canGoNext = computed(() => visibleMonth.value.getTime() < maxMonth.value);
const calendarDays = computed(() => {
    const first = new Date(visibleMonth.value.getFullYear(), visibleMonth.value.getMonth(), 1, 12);
    const mondayIndex = (first.getDay() + 6) % 7;
    const start = new Date(first);
    start.setDate(first.getDate() - mondayIndex);
    return Array.from({ length: 42 }, (_, index) => {
        const day = new Date(start);
        day.setDate(start.getDate() + index);
        const key = dateKey(day);
        return {
            key,
            label: day.getDate(),
            inMonth: day.getMonth() === visibleMonth.value.getMonth(),
            disabled: key < props.today || key > props.maxDate,
        };
    });
});

function shiftMonth(offset) {
    visibleMonth.value = new Date(visibleMonth.value.getFullYear(), visibleMonth.value.getMonth() + offset, 1, 12);
}
</script>

<template>
    <section class="booking-calendar" aria-label="Chọn ngày đặt sân">
        <header class="booking-calendar-header">
            <button type="button" aria-label="Tháng trước" :disabled="!canGoPrevious" @click="shiftMonth(-1)">‹</button>
            <h3>{{ monthTitle }}</h3>
            <button type="button" aria-label="Tháng sau" :disabled="!canGoNext" @click="shiftMonth(1)">›</button>
        </header>
        <div class="booking-calendar-grid booking-calendar-weekdays" aria-hidden="true">
            <span v-for="day in weekdayLabels" :key="day">{{ day }}</span>
        </div>
        <div class="booking-calendar-grid">
            <button
                v-for="day in calendarDays"
                :key="day.key"
                type="button"
                :disabled="day.disabled"
                :class="{ 'is-outside': !day.inMonth, 'is-selected': props.date === day.key, 'is-today': today === day.key }"
                :aria-pressed="props.date === day.key"
                @click="emit('select', day.key)"
            >{{ day.label }}</button>
        </div>
        <p class="booking-calendar-note">Chỉ nhận đặt trong 30 ngày tới.</p>
    </section>
</template>
