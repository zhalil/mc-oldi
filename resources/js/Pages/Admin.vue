<script setup>
const props = defineProps({
    users: Array
});
const disableUser = () => {

}
</script>

<template>
    <v-container>
        <v-table>
            <thead>
            <tr>
                <th class="text-left">
                    Домен
                </th>
                <th class="text-left">
                    Осталось дней
                </th>

                <th class="text-left">
                    почта
                </th>

                <th class="text-left">
                    активен
                </th>


                <th class="text-left">
                    добавить 30 дней
                </th>


            </tr>
            </thead>
            <tbody>
            <tr
                v-for="user in users"
                :key="user.id"
            >
                <td>{{ user.domain ?? 'домен отсутствует' }}</td>
                <td>{{ user.paid_days }}</td>
                <td>{{ user.email }}</td>
                <td>
                    <v-btn color="red" v-if="user.is_active">
                        выключить
                    </v-btn>
                    <v-btn color="green" v-if="!user.is_active">
                        включить
                    </v-btn>
                </td>
                <td><v-btn color="yellow">добавить</v-btn></td>

            </tr>
            </tbody>
        </v-table>
    </v-container>
</template>

<style scoped>

</style>
