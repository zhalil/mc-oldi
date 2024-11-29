<script setup lang="ts">
import {useAmo} from "@/hooks/use-amo";
import {router, usePage, Link} from "@inertiajs/vue3";
import {onMounted} from "vue";
import useAmo2 from "@/hooks/use-amo2";

const props = defineProps({
    secretUrl: String,
    codeUrl: String,
    domain: String,
    paid_days: Number,
    is_active: Boolean,
    is_auth: Boolean,
    id: String
});

const scr = `
    <script
   amoScript.setAttribute("class","amocrm_oauth");
   amoScript.setAttribute("charset","utf-8")
   amoScript.setAttribute("data-name","Integration name");
   amoScript.setAttribute("data-description","Integration description");
   amoScript.setAttribute("data-redirect_uri","${props.codeUrl}");
   amoScript.setAttribute("data-secrets_uri","${props.secretUrl}");
   amoScript.setAttribute("data-logo","https://${location.host}/logo.png");
   amoScript.setAttribute("data-scopes","crm,notifications");
   amoScript.setAttribute("data-title","Button");
   amoScript.setAttribute("data-compact","false");
   amoScript.setAttribute("data-class-name","className");
   amoScript.setAttribute("data-color","default");
   amoScript.setAttribute("data-state","state");
   amoScript.setAttribute("data-error-callback","functionName");
   amoScript.setAttribute("data-mode","popup");
   amoScript.setAttribute("src","https://www.amocrm.ru/auth/button.min.js");
><\/script>`;


const page = usePage();
const amoUrl = useAmo(props.domain,props.codeUrl,props.secretUrl);
const handle = () => {

    window.open(_, $, "scrollbars, status, resizable");

    //const win = window.open(amoUrl, "_blank");
    // const win = window.open(
    //     amoUrl,
    //     'myWindow',
    //     "location=yes,width=600,height=600"
    // );
    // const timer = setInterval(function() {
    //     //@ts-ignore
    //     if(win.closed) {
    //         clearInterval(timer);
    //         location.reload();
    //     }
    // }, 1000);

}
onMounted(()=>{
   const element  = document.querySelector("#amo-auth-button");
   if(typeof element === "object") {
       let amoScript = document.createElement('script')
       amoScript.setAttribute("class","amocrm_oauth");
       amoScript.setAttribute("charset","utf-8")
       amoScript.setAttribute("data-name","oldiagency объеденение дублей контактов");
       amoScript.setAttribute("data-description","объеденение дублей контактов");
       amoScript.setAttribute("data-redirect_uri",`${props.codeUrl}`);
       amoScript.setAttribute("data-secrets_uri",`${props.secretUrl}`);
       amoScript.setAttribute("data-logo",`https://${location.host}/logo.png`);
       amoScript.setAttribute("data-scopes","crm,notifications");
       amoScript.setAttribute("data-title","Войти в амо");
       amoScript.setAttribute("data-compact","false");
       amoScript.setAttribute("data-class-name","className");
       amoScript.setAttribute("data-color","default");
       amoScript.setAttribute("data-state","state");
       amoScript.setAttribute("data-error-callback","functionName");
       amoScript.setAttribute("data-mode","popup");
       amoScript.setAttribute("src","https://www.amocrm.ru/auth/button.min.js");
       element.appendChild(amoScript);
       useAmo2();
   }

});
const changeActive = () => {
    // router.get(route('change.active.mode',{id: props.id}), {
    //     _token: page.props.csrf_token
    // });

    router.get(route('change.active.mode',{id: props.id}));
}

setTimeout(()=>{
    location.reload();
}, 1000 * 60 * 8);

//https://mergecontacts.365a.kz/code
//https://mergecontacts.365a.kz/secret
</script>

<template>
    <div style="margin: 10px">
        <div v-if="!props.is_auth" id="amo-auth-button">

        </div>
        <div v-else>
            <p>осталось дней: {{paid_days}}</p>
            <v-btn @click="changeActive" v-if="is_active" color="red">
                выключить
            </v-btn>
            <v-btn @click="changeActive" v-if="!is_active" color="green">включить</v-btn>
        </div>
    </div>
</template>
