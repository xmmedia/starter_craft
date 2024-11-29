<template>
    <nav class="w-full lg:w-auto" role="navigation" aria-label="Main">
        <ul class="flex flex-nowrap list-none">
            <li class="header-nav_item">
                <a href="{{ url('about') }}">About</a>
            </li>
            <li class="header-nav_item header-nav_item-products">
                <a href="/">Products</a>
            </li>
            <li class="header-nav_item">
                <a href="{{ url('contact') }}">Contact</a>
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
        </ul>
    </nav>
</template>
<script setup>
import { ref } from 'vue';

const showMobileMenu = ref(false);

const toggleMobileMenu = () => {
    showMobileMenu.value = !showMobileMenu.value;
};

window.addEventListener('resize', () => { showMobileMenu.value = false });

</script>
