<script setup>
import generateLink from "@/Pages/Hooks/generate-link.js";
const link = generateLink();
</script>

<template>
<v-btn :href="link">auth</v-btn>
</template>

<style scoped>

</style>
