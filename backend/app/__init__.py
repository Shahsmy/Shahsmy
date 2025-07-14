from flask import Flask

def create_app():
    app = Flask(__name__)

    # Dummy route
    @app.route('/')
    def index():
        return "Hello, World!"

    return app
