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
                                    "js\/backend\/pagemyself-page-editor.js"
                                ]
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    },
                    "form": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "js\/form"

                            }
                        ]
                    },
                    "pagemyself": {
                        "files": [
                            {
                                "type": "file",
                                "path": [
                                    "js\/pagemyself.js",
                                    "js\/pagemyself-theme.js"
                                ]
                            }
                        ]
                    },
                    "components": {
                        "files": [
                            {
                                "type": "file",
                                "path": [
                                    "js\/pagemyself-component.js"
                                ]
                            },
                            {
                                "type": "folder",
                                "path": "js\/components"

                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    },
                    "tinymce-plugins": {
                        "files": [
                            {
                                "type": "file",
                                "path": [
                                    "js\/tinymce-plugins.js",
                                    "js\/tinymce-templates.js"
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
                    "form": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "scss\/form"
                            }
                        ]
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
                    },
                    "components": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "scss\/components"
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
