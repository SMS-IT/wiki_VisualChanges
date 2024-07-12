document.documentElement.className = document.documentElement.className.replace( /(^|\s)client-nojs(\s|$)/, "$1client-js$2" );

//Состояние кнопки свернуть все/развернуть все.
var isAllCollapsed=true;
var currScrollPos;
var newScrollPos;

//Селекторы
var collapsableSelector='.mw-collapsible';
var collapsibleToggleSelector='.mw-collapsible-toggle';
var collapsibleContentSelector='.mw-collapsible-content';
var collapsibleTextSelector='.mw-collapsible-toggle>a';
var expandAllSelector='#epandAll';

//Классы
var collapsedClass='mw-collapsed';
var expandedClass='mw-collapsible-toggle-expanded';
var collapsedToggleClass='mw-collapsible-toggle-collapsed';


// Функция раскрытия и закрытия всех фргаментов , которые могут сворачиваться и разворачиваться
function collapseOrExpandAllPlugins(){
	if(isAllCollapsed){
		expandAllplugins();
	}
	else{
   	collapseAllplugins();
	}
	isAllCollapsed=!isAllCollapsed;
	
} 

//Свернуть все плагины
function collapseAllplugins(){
	$(collapsableSelector).addClass(collapsedClass);
	$(collapsibleToggleSelector).removeClass(expandedClass);
	$(collapsibleToggleSelector).addClass(collapsedToggleClass);
	$(collapsibleContentSelector).hide();
	$(collapsibleTextSelector).text('развернуть');
	$(expandAllSelector).html('<img src=/skins/common/images/plus-icon.jpg height=18 width=18>')
}

 //Развернуть все плагины
function expandAllplugins(){
    $(collapsableSelector).removeClass(collapsedClass);
	$(collapsibleToggleSelector).addClass(expandedClass);
	$(collapsibleToggleSelector).removeClass(collapsedToggleClass);
	$(collapsibleContentSelector).show();
	$(collapsibleTextSelector).text('свернуть');
	$(expandAllSelector).html('<img src=/skins/common/images/minus-icon.jpg height=18 width=18>')
}
function scrollToTop() {
	 document.body.scrollTop = 0; // For Safari
    document.documentElement.scrollTop = 0; // For Chrome, FF, IE, etc
}

//После прогрузки страницы отображаем элемент срытия/раскрытия всего только если есть хотя бы один плагин
$( document ).ready(function() {
		if(!$(collapsableSelector).length){
	       $(expandAllSelector).hide();
		}
});

function showdiv(divname, disptype) {
	if (document.getElementById(divname).style.display == disptype)
		document.getElementById(divname).style.display = 'none';
	else
		document.getElementById(divname).style.display = disptype;
}