export default function () {
    const scheme = 'https://';
    const secret = `${scheme}${location.hostname}/secret`;
    const code = `${scheme}${location.hostname}/code`;
    const logo = 'https://a.storyblok.com/f/157376/318x318/98b41733f1/amocrmtiny.png/m/';
    return `https://www.amocrm.ru/oauth/?state=state&mode=popup&origin=${location.origin}&name=contactmerger&description=contactmerger&redirect_uri=${code}&secrets_uri=${secret}&logo=${logo}&scopes[]=crm&scopes[]=notifications`;
}
