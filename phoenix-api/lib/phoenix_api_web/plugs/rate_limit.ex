defmodule PhoenixApiWeb.Plugs.RateLimit do
  @moduledoc """
  Plug enforcing two independent sliding-window rate limits via RateLimiter GenServer.

  Must be placed **after** `PhoenixApiWeb.Plugs.Authenticate` so that
  `conn.assigns.current_user` is already populated.

  Limits:
    - Per-user:  5 requests / 10 minutes
    - Global:    1 000 requests / hour

  On limit exceeded: 429 Too Many Requests + JSON `{"error": "rate_limit_exceeded"}`.
  """

  import Plug.Conn
  import Phoenix.Controller

  alias PhoenixApi.RateLimit.RateLimiter

  def init(opts), do: opts

  def call(conn, _opts) do
    user_id = conn.assigns.current_user.id

    with :ok <- RateLimiter.check_and_record_user(user_id),
         :ok <- RateLimiter.check_and_record_global() do
      conn
    else
      {:error, :rate_limited} ->
        conn
        |> put_status(:too_many_requests)
        |> json(%{error: "rate_limit_exceeded"})
        |> halt()
    end
  end
end

