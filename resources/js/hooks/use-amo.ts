export function useAmo(subdomain: string | undefined, authCodeUrl: string | undefined, secretUrl: string | undefined) {
    console.log(authCodeUrl);
    return `https://www.amocrm.ru/oauth/?state=state&mode=popup&origin=https://${location.host}/dashboard2?domain=${subdomain}&name=oldi%20merge%20contacts&description=oldi%20merge%20contacts&redirect_uri=${authCodeUrl}&secrets_uri=${secretUrl}&logo=https://${location.host}/logo.png&scopes[]=crm&scopes[]=notifications`;
}
