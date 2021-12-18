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
        "setupDoneRedirect": "/backend",
        "modules": {
            "Calendar": "Calendar",
            "ContactForm": "ContactForm",
            "Guestbook": "Guestbook",
            "ImageGallery": "ImageGallery",
            "Slideshow": "Slideshow"
        },
        "userRoles": {
            "content": "__myself_user_role_content__",
            "settings": "__myself_user_role_settings__",
            "nav": "__myself_user_role_nav__",
            "page": "__myself_user_role_page__"
        },
        "backendLogo": "img\/logo-colored-white.svg",
        "backendIcon": "img\/logo-squared.png",
        "compiler": {
            "Myself": {
                "js": {
                    "myself": {
                        "files": [
                            {
                                "type": "file",
                                "path": [
                                    "js\/myself.js",
                                    "js\/myself-pageblocks.js"
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
                    "myself-edit": {
                        "files": [
                            {
                                "type": "file",
                                "path": "js\/myself-edit.js"
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    },
                    "tinymce": {
                        "files": [
                            {
                                "type": "file",
                                "path": "js\/tinymce-plugins.js"
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    },
                    "pageblock-columns": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "js\/page-blocks\/columns",
                                "recursive": true
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    },
                    "theme-hello": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "js\/themes\/hello",
                                "recursive": true
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    }
                },
                "scss": {
                    "myself": {
                        "files": [
                            {
                                "type": "file",
                                "path": "scss\/myself.scss"
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
                    "myself-edit": {
                        "files": [
                            {
                                "type": "file",
                                "path": "scss\/myself-edit.scss"
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    },
                    "pageblock-columns": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "scss\/page-blocks\/columns",
                                "recursive": true
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    },
                    "pageblock-navigation": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "scss\/page-blocks\/navigation",
                                "recursive": true
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    },
                    "theme-hello": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "scss\/themes\/hello",
                                "recursive": true
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    }
                }
            }
        },
        "systemEventLog": {
            "1": false,
            "2": false,
            "3": false,
            "4": true,
            "5": true
        }
    }
</script>
