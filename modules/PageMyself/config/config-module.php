<?php

// prevent loading directly in the browser without framelix context
if (!defined("FRAMELIX_MODULE")) {
    die();
}
// this config represents the editable configuration that can be changed by the user in the admin interface
// this should not be under version control
?>
<script type="application/json">
    {
        "modulesCallable": "Framelix\\PageMyself\\Utils\\ModuleUtils::getModules",
        "backendDefaultView": "Framelix\\PageMyself\\View\\Backend\\Index",
        "userRoles": {
            "content": "__myself_user_role_content__"
        },
        "backendLogo": "img\/logo-colored-white.svg",
        "backendIcon": "img\/logo-squared.svg",
        "compiler": {
            "PageMyself": {

            }
        }
    }
</script>
