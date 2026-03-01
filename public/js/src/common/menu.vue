<template>
    <nav class="w-full lg:w-auto" :class="{ 'header-mobile-open': showMobileMenu }" role="navigation" aria-label="Main">
        <ul class="flex flex-nowrap list-none">
            <li v-for="(item,key) in items" :key="key" class="header-nav_item">
                <a :href="item.url" class="header_nav-link" @click="showMobileMenu = false">{{ item.label }}</a>
            </li>

            <li v-if="!showMobileMenu" class="header-nav_item header-nav_item-toggle">
                <button type="button"
                        class="button-link"
                        @click="toggleMobileMenu">+ More</button>
            </li>
            <li v-else class="header-nav_item header-nav_item-toggle">
                <button type="button"
                        class="button-link"
                        @click="toggleMobileMenu">– Less</button>
            </li>

            <li v-if="hasButton">
                <a :href="menuButton.value" class="button">{{ menuButton.label }}</a>
            </li>
        </ul>
    </nav>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    items: {
        type: String,
        required: true,
    },
    menuButton: {
        type: String,
        default: null,
    },
});

const items = ref(JSON.parse(props.items));
const menuButton = ref(JSON.parse(props.menuButton));
const showMobileMenu = ref(false);

const hasButton = computed(() => menuButton.value?.url && menuButton.value?.label);

const toggleMobileMenu = () => {
    showMobileMenu.value = !showMobileMenu.value;
};

window.addEventListener('resize', () => { showMobileMenu.value = false });
</script>
