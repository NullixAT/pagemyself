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
        "backendLogo": "img\/logo-colored-white.svg",
        "backendIcon": "img\/logo-squared.svg",
        "compiler": {
            "PageMyself": {
                "js": {
                    "pageeditor": {
                        "files": [
                            {
                                "type": "file",
                                "path": [
                                    "js\/backend\/pageeditor.js"
                                ]
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    },
                    "pagemyself": {
                        "files": [
                            {
                                "type": "file",
                                "path": [
                                    "js\/pagemyself.js"
                                ]
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    }
                },
                "scss": {
                    "pageeditor": {
                        "files": [
                            {
                                "type": "file",
                                "path": "scss\/backend\/pageeditor.scss"
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    },
                    "pagemyself": {
                        "files": [
                            {
                                "type": "file",
                                "path": [
                                    "scss\/pagemyself.scss"
                                ]
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    }
                }
            }
        }
    }
</script>
