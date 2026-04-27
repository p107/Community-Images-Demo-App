defmodule PhoenixApiWeb.Router do
  use PhoenixApiWeb, :router

  pipeline :api do
    plug :accepts, ["json"]
  end

  pipeline :authenticated do
    plug PhoenixApiWeb.Plugs.Authenticate
    plug PhoenixApiWeb.Plugs.RateLimit
  end

  scope "/api", PhoenixApiWeb do
    pipe_through [:api, :authenticated]

    get "/photos", PhotoController, :index
    get "/photos/:id", PhotoController, :show
  end
end
