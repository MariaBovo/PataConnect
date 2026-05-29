class IPC:
    published_functions = {}

    @classmethod
    def publish(cls, func):
        cls.published_functions[func.__name__] = func
        return func

    @classmethod
    def execute_published_function(cls, call_name, parameters):
        if call_name not in cls.published_functions:
            raise ValueError(f"Function '{call_name}' is not registered.")
        
        func = cls.published_functions[call_name]
        
        if isinstance(parameters, dict):
            return func(**parameters)
        elif isinstance(parameters, list):
            return func(*parameters)
        else:
            return func(parameters)