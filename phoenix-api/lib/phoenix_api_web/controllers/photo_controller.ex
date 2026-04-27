defmodule PhoenixApiWeb.PhotoController do
  use PhoenixApiWeb, :controller

  alias PhoenixApi.Repo
  alias PhoenixApi.Media.Photo
  import Ecto.Query

  plug PhoenixApiWeb.Plugs.Authenticate

  def index(conn, _params) do
    current_user = conn.assigns.current_user

    photos =
      Photo
      |> where([p], p.user_id == ^current_user.id)
      |> select([p], %{id: p.id, photo_url: p.photo_url})
      |> Repo.all()

    json(conn, %{photos: photos})
  end

  def show(conn, %{"id" => id}) do
    current_user = conn.assigns.current_user

    photo =
      Photo
      |> where([p], p.id == ^id and p.user_id == ^current_user.id)
      |> Repo.one()

    case photo do
      nil ->
        conn
        |> put_status(:not_found)
        |> json(%{error: "Photo not found"})

      photo ->
        json(conn, %{
          photo: %{
            id: photo.id,
            photo_url: photo.photo_url,
            location: photo.location,
            description: photo.description,
            camera: photo.camera,
            taken_at: photo.taken_at
          }
        })
    end
  end
end
