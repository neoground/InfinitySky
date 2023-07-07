# Config file handling

import json


class ConfigManager:
    def __init__(self, main_config_path="/opt/infinitysky/config/main.json",
                 user_config_path="/opt/infinitysky/config/user.json"):
        self.main_config_path = main_config_path
        self.user_config_path = user_config_path
        self.config = self.load_config()

    def merge_dicts(self, a, b):
        for key in b:
            if key in a:
                if isinstance(a[key], dict) and isinstance(b[key], dict):
                    self.merge_dicts(a[key], b[key])
                else:
                    a[key] = b[key]
            else:
                a[key] = b[key]
        return a

    def load_config(self):
        with open(self.main_config_path) as f:
            main_config = json.load(f)

        try:
            with open(self.user_config_path) as f:
                user_config = json.load(f)
        except FileNotFoundError:
            user_config = {}

        return self.merge_dicts(main_config, user_config)

    def get(self, key_path, default=None):
        keys = key_path.split('.')
        value = self.config
        for key in keys:
            if key in value:
                value = value[key]
            else:
                return default
        return value
