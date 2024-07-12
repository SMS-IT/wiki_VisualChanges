<?php

class ChangesHooks
{

    public static $tag_list = array();
    public static $tag_request = array();
    public static $counter = 0;

    public static $redmine_link = ""; // адрес задач Redmine, например https://redmine/issues/
    public static $accept_changes_link = ""; //

    # Setup the AnyWikDraw parser function
    public static function efChangesParserFirstCallInit(Parser &$parser)
    {
        $parser->setHook('change', array('ChangesHooks', 'changesParserHookTag'));
        return true;
    }

    // xml tag <change task="xxx">frame</change>
    public static function changesParserHookTag($text, $args, $parser)
    {
        $parser->disableCache();
        $isProtected = $parser->getTitle()->isProtected();
        $out = "";

        $action = isset($_POST['action']) ? $_POST['action'] : "";
        if ($action == 'parse') {
            return $text;
        }

        $hideall = false;

        if ($args["tag"] && !in_array(mb_strtolower($args["tag"]), self::$tag_list)) {
            $cur_tag_arr = explode(",", $args["tag"]);
            foreach ($cur_tag_arr as $cur_tag) {
                if (!in_array(str_replace("кроме ", "", mb_strtolower(trim($cur_tag))), self::$tag_list)) {
                    array_push(self::$tag_list, str_replace("кроме ", "", mb_strtolower(trim($cur_tag))));
                }
            }
        }
        if (count(self::$tag_request) == 0 && isset($_GET['tag0'])) {
            while (isset($_GET['tag' . self::$counter])) {
                if ($_GET['tag' . self::$counter] == "hideall") {
                    $hideall = true;
                    return "";
                } else
                    array_push(self::$tag_request, mb_strtolower($_GET['tag' . self::$counter]));
                self::$counter += 1;
            }
        }
        // если указан параметр фильтрации по тегу то выводим только ченжи с вхождением нужного тега
        if ($args["tag"] && count(self::$tag_request) > 0) {
            $cur_tag_arr = explode(",", $args["tag"]);
            $has_match = false;
            foreach ($cur_tag_arr as $item) {
                if (in_array(mb_strtolower(trim($item)), self::$tag_request)) {
                    $has_match = true;
                    break;
                }
            }
            if (!isset($_GET['changesmode']) || (isset($_GET['changesmode']) && $_GET['changesmode'] == 'show')) {
                if (!$has_match) {
                    return "";
                }
            } elseif (isset($_GET['changesmode']) && $_GET['changesmode'] == 'hide') {
                if ($has_match) {
                    return "";
                }
            }
        }

        $internalout = $parser->internalParse($text);
        $title = "";
        $tagClass = "";
        $taskClass = "";
        $commonClass = "changes";

        // пишем глобальный номер задачи в пагес-пропс
        if ($args["forpage"] && ($args["task"] || ($args["tag"]))) {
            $prop = $args["task"];
            if ($args["tag"])
                $prop .= "|" . $args["tag"];
            $parser->getOutput()->setProperty('changesPage', $prop);
        }

        // показываем ченжи
        $tagName = "span";
        $accept_changes_inline = "";
        $accept_changes_block = "";
        if (strpos($internalout, "\n") !== false) {
            $tagName = "div";
            $commonClass = "changes-right";
        }

        if ($tagName == "div") {
            if ($args["tag"]) {
                $tagClass = "changes-tag-right";
            }
            if ($args["task"]) {
                $commonClass .= " in-develop";
                $taskClass = "changes-task-right changes-margin-right";
                $accept_changes_block = "<span title='Принять изменения' class='accept_changes_block'><a href='" . self::$accept_changes_link . "?task=" . str_replace("#", "", $args["task"]) . "'>✔</a></span>";
                $accept_changes_inline = "";
            } else {
                $commonClass .= " in-release";
                if ($tagClass != "") {
                    $tagClass .= " changes-margin-right";
                }
            }
        } else {
            if ($args["tag"]) {
                $tagClass = "changes-tag";
            }
            if (array_key_exists("task", $args) && $args["task"]) {
                $commonClass .= " in-develop";
                $taskClass = "changes-task";
                $accept_changes_inline = "<span title='Принять изменения' class='accept_changes_inline'><a href='" . self::$accept_changes_link . "?task=" . str_replace("#", "", $args["task"]) . "'>✔</a></span>";
                $accept_changes_block = "";
            } else {
                $commonClass .= " in-release";
            }
        }
        if ($tagClass != "") {
            $title .= "<span class='" . $tagClass . "'>" . $args["tag"] . "</span>";
        }
        if ($taskClass != "") {
            $title .= "<span class='" . $taskClass . "'><a href='" . self::$redmine_link . str_replace("#", "", $args["task"]) . "'>" . "#" . str_replace("#", "", $args["task"]) . "</a>" . "</span>";
        }

        $out .= "<" . $tagName . " class='" . $commonClass . "'>" . $title . $accept_changes_block . "<" . $tagName . " class='changes-content'>" . $internalout . $accept_changes_inline . "</" . $tagName . "></" . $tagName . ">";
        $return = array($out, 'isHTML' => true);
        return $out;
    }

    public static function contentHook($skin, array &$content_actions)
    {
        global $wgRequest, $wgUser;
        // Use getRelevantTitle if present so that this will work on some special pages
        $title = method_exists($skin, 'getRelevantTitle') ?
            $skin->getRelevantTitle() : $skin->getTitle();
        if ($title->getNamespace() !== NS_SPECIAL) {
            $action = $wgRequest->getText('action');
            $content_actions['actions']['acceptpagechangeshistory'] = array(
                'class' => $action === 'acceptpagechangeshistory' ? 'selected' : false,
                'text' => wfMessage('acceptpagechangeshistory')->text(),
                'href' => self::$accept_changes_link . "?page=$title",
                'target' => "_blank"
            );
        }

        return true;
    }

    public static function ChangesFilter_BeforeHTML(&$out, &$text)
    {
        global $wgTitle, $wgUser;

        if ($wgTitle == null) return true;
        if (isset($_GET['printable']) && $_GET["printable"] == "yes") return true;
        $counter = 0;
        $tag_dic_text = "var tag_dic = {};";

        // добавляем JS-скрипт, с разными методами для работы с модальным окном и HTML для самого модального окна
        $text2 = "<script>

		var modalDiv = document.getElementById(\"change_tag_filter_modal\");
		if (!modalDiv) {
			modalDiv = document.createElement(\"div\");
		
			modalDiv.setAttribute(\"id\", \"change_tag_filter_modal\");
			modalDiv.setAttribute(\"style\", \"display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);\");
			var inner = \"<div style=\\\"background-color: #fefefe; margin: 15% auto; padding: 20px; border: 10px solid #2781c5; width: fit-content;\\\">\";";

        $text2 .= "
           inner += \"<div><div class='changes_radio_mode'><input id='changesmodeshow' type='radio' name='changesmode' value='show'  onclick=\\\"changeModeChanges(event)\\\"><label for='changesmodeshow'>Показать</label></div>\";
           inner += \"<div class='changes_radio_mode'><input id='changesmodehide' type='radio' name='changesmode' value='hide' onclick=\\\"changeModeChanges(event)\\\"><label for='changesmodehide'>Скрыть</label></div></div>\";
		";
        foreach (self::$tag_list as $tag) {
            $text2 .= "
			inner += \"<div><input id=\\\"change_tag_$counter\\\" type=\\\"checkbox\\\" name=\\\"change_tag_$counter\\\"/> <label for=\\\"change_tag_$counter\\\">$tag</label></div>\";";

            $tag_dic_text .= "
			tag_dic[\"change_tag_$counter\"] = \"$tag\";";
            $counter += 1;
        }
        $text2 .= "
			inner += \"<hr style='margin: 15px 0px;'>\";
			inner += \"<div><input id=\\\"change_tag_all\\\" type=\\\"checkbox\\\" name=\\\"change_tag_all\\\"/> <label for=\\\"change_tag_all\\\">Все</label></div>\";
			inner += \"</br><div><button style='padding: 2px 15px;' onclick=\\\"acceptSelectedChanges()\\\" >Принять</button>&nbsp;&nbsp;\";
			inner += \"<button style='padding: 2px 15px;' onclick=\\\"DismissChangeModal()\\\" >Отмена</button></div></div>\";
			modalDiv.innerHTML = inner;
			var lastChild = document.body.lastChild;
			document.body.insertBefore(modalDiv, lastChild.nextSibling);
		}
		$tag_dic_text
		
		$(window).load(function() {
			if (tag_dic && tag_dic['change_tag_0']) {
			    var path = new URL(window.location.href);
			    var changesEntries = Object.fromEntries(path.searchParams.entries());
			    var hasChangesUrlParam = false;
			    var changesDivContent = \"<p>\" + (changesEntries[\"changesmode\"] == \"show\" ? \"Выбраны\" : \"Скрыты\") + \" теги: \";
			    var changesKeysForDiv = Object.keys(changesEntries);
			    var availableChangesCounter = 0
			    for (let i =0; i < changesKeysForDiv.length; i++) {
			        if (/tag\d+/.test(changesKeysForDiv[i])) {
			            if (availableChangesCounter > 0) changesDivContent += \", \";
			            changesDivContent += changesEntries[changesKeysForDiv[i]];
			            hasChangesUrlParam = true;
			            availableChangesCounter += 1;
			        }
			    }
			    changesDivContent += \"</p>\";
			    if (hasChangesUrlParam) {
                    var changesDivHint = document.createElement(\"div\");
                    changesDivHint.setAttribute(\"id\", \"changesDivHint\");
                    changesDivHint.setAttribute(\"class\", \"mw-body\");
                    changesDivHint.setAttribute(\"style\", \"margin-left: 12.3em; padding: 0px 3px; font-size: small; display: block; position: relative; background-color: cornsilk;\");
                    changesDivHint.innerHTML = changesDivContent;
                    $(\"#mw-head-base\").after(changesDivHint.outerHTML);
             }
             
				var menuElement = document.createElement(\"li\");
				menuElement.innerHTML = '<span><a onclick=\"OpenChangeModal()\" href=\"#\" title=\"Теги изменений\"><img src=\"/skins/common/images/changes_tag_filter.png\"</a></span>';
				var elementBefore = document.getElementById(\"ca-watch\");
				if (elementBefore) {
					elementBefore.parentElement.insertBefore(menuElement, elementBefore);
				} else {
					elementBefore = document.getElementById(\"ca-unwatch\");
					if (elementBefore) {
						elementBefore.parentElement.insertBefore(menuElement, elementBefore);
					}
				}
			}
		});
		
		function changeModeChanges(e) {
		    var path = new URL(window.location.href);
		    path.searchParams.set(\"changesmode\", e.target.value);
		}
		
		function OpenChangeModal() {
			var urlSearchParamsChanges = new URLSearchParams(window.location.search);
			var changesParams = Object.fromEntries(urlSearchParamsChanges.entries());
			if (changesParams[\"changesmode\"]) {
			    var radios = $(\"#change_tag_filter_modal input[type=radio]\");
			    if (changesParams[\"changesmode\"] == \"show\") {
			        radios[0].checked = true;
			    } else {
			        radios[1].checked = true;
			    }
			}
			var allChangesCheckboxes = $(\"#change_tag_filter_modal input[type=checkbox]\");
			
			// Для совместимости с VisualSmsMetaTags
			var changesKeys = Object.keys(changesParams);
	       		for (let i = 0; i < changesKeys.length; i++) {
              			if (changesKeys[i].includes(\"smsmetas\")) {
                  			delete changesParams[changesKeys[i]];
              			}
          		}
			changesKeys = Object.keys(changesParams);
			for (let i =0; i< allChangesCheckboxes.length; i++) {
    				for (let j = 0; j < changesKeys.length; j++) {
					if (allChangesCheckboxes[i].nextSibling.nextSibling.innerText == changesParams[changesKeys[j]]) {
						allChangesCheckboxes[i].checked = true;
					}
					if (allChangesCheckboxes[i].nextSibling.nextSibling.innerText == \"Все\" && changesParams[changesKeys[j]] == \"hideall\") allChangesCheckboxes[i].checked = true;
				}
			}
			modalDiv.style[\"display\"] = \"block\";
		}
		
		function DismissChangeModal() {
			modalDiv.style[\"display\"] = \"none\";
		}
		
		function HideSelectedChanges(selectedChanges, path, allAvailableChangesTags) {			
			for (let i = 0; i < selectedChanges.length; i++) {
				var changesVal = selectedChanges[i];
				if (changesVal == \"Все\") {
					for (let j = 0; j < allAvailableChangesTags.length; j++) {
						path.searchParams.delete(\"tag\"+ j);
					}				
					changesVal = \"hideall\";
					path.searchParams.append(\"tag\" + 0, changesVal);
					break;
				}
				path.searchParams.append(\"tag\" + i, changesVal);
			}
			return path;
		}
		
		function ShowSelectedChanges(selectedChanges, path, allAvailableChangesTags) {
			for (let i = 0; i < selectedChanges.length; i++) {
				var changesVal = selectedChanges[i];
				if (changesVal == \"Все\") {
					for (let j = 0; j < allAvailableChangesTags.length; j++) {
						path.searchParams.delete(\"tag\"+ j);
					}					
					break;
				}
				path.searchParams.append(\"tag\" + i, changesVal);
			}
			return path;
		}
		
		function acceptSelectedChanges() {
			var path = new URL(window.location.href);
						
			var selectedChanges = []; 
			$(\"#change_tag_filter_modal input[type=checkbox]:checked\").each(function() {
				selectedChanges.push($(this)[0].nextSibling.nextSibling.innerText);
			});
			
			var allAvailableChangesTags = $(\"#change_tag_filter_modal input[type=checkbox]\");
			for (let i = 0; i< allAvailableChangesTags.length; i++) {
				path.searchParams.delete(\"tag\"+ i);
			}
			
			var selectedMode = $(\"#change_tag_filter_modal input[type=radio]:checked\");
			if (selectedMode.length == 1) {
			    if (selectedMode[0].value == \"show\") {
			        path.searchParams.set(\"changesmode\", \"show\");
			        path = ShowSelectedChanges(selectedChanges, path, allAvailableChangesTags);
			    } else {
			        path.searchParams.set(\"changesmode\", \"hide\");
			        path = HideSelectedChanges(selectedChanges, path, allAvailableChangesTags);
			    }   
			}
			
			modalDiv.style[\"display\"] = \"none\";
			window.location.replace(window.location.pathname + path.search);
		}
		</script>";

        $text = $text2 . $text;

        return true;
    }
}

?>

