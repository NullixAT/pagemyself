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
        "compiler": {
            "Calendar": {
                "js": {
                    "pageblock-calendar": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "js\/page-blocks\/calendar",
                                "recursive": true
                            }
                        ],
                        "options": {
                            "noInclude": true
                        }
                    }
                },
                "scss": {
                    "pageblock-calendar": {
                        "files": [
                            {
                                "type": "folder",
                                "path": "scss\/page-blocks\/calendar",
                                "recursive": true
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
