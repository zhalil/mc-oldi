<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
const form = reactive({
    domain: null,
    client_id:null,
    redirect_uri: null,
    client_secret: null,
    code:''
});
function submit() {
    router.post('/auth/manual', form)
}

</script>

<template>
    <form @submit.prevent="submit" class="container">
        <div style="width: 550px">
            <v-text-field v-model="form.domain" label="domain"></v-text-field>
            <v-text-field v-model="form.client_id" label="client_id"></v-text-field>
            <v-text-field label="client_secret" v-model="form.client_secret"></v-text-field>
            <v-text-field label="redirect_uri" v-model="form.redirect_uri"></v-text-field>
            <v-text-field label="code" v-model="form.code"></v-text-field>
            <v-btn type="submit">save</v-btn>
        </div>
    </form>
</template>

<style scoped>
.container {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
}
</style>
