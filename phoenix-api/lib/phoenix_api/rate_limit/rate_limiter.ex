defmodule PhoenixApi.RateLimit.RateLimiter do
  @moduledoc """
  OTP GenServer implementing sliding-window rate limiting.

  Two independent limits are enforced:
    - Per-user:  max 5 imports per 10 minutes
    - Global:    max 1000 imports per hour

  State shape:
    %{
      {:user, user_id} => [unix_ms_timestamp, ...],
      :global          => [unix_ms_timestamp, ...]
    }
  """

  use GenServer

  @user_limit      5
  @user_window_ms  10 * 60 * 1_000   # 10 minutes
  @global_limit    1_000
  @global_window_ms  60 * 60 * 1_000 # 1 hour

  # ---------------------------------------------------------------------------
  # Public API
  # ---------------------------------------------------------------------------

  def start_link(opts \\ []) do
    GenServer.start_link(__MODULE__, %{}, Keyword.put_new(opts, :name, __MODULE__))
  end

  @doc """
  Checks per-user limit and, if allowed, records the request.
  Returns `:ok` or `{:error, :rate_limited}`.
  """
  @spec check_and_record_user(user_id :: any()) :: :ok | {:error, :rate_limited}
  def check_and_record_user(user_id) do
    GenServer.call(__MODULE__, {:check_and_record_user, user_id})
  end

  @doc """
  Checks global limit and, if allowed, records the request.
  Returns `:ok` or `{:error, :rate_limited}`.
  """
  @spec check_and_record_global() :: :ok | {:error, :rate_limited}
  def check_and_record_global do
    GenServer.call(__MODULE__, :check_and_record_global)
  end

  # ---------------------------------------------------------------------------
  # GenServer callbacks
  # ---------------------------------------------------------------------------

  @impl true
  def init(state), do: {:ok, state}

  @impl true
  def handle_call({:check_and_record_user, user_id}, _from, state) do
    now  = now_ms()
    key  = {:user, user_id}
    hits = Map.get(state, key, []) |> drop_old(now - @user_window_ms)

    if length(hits) >= @user_limit do
      {:reply, {:error, :rate_limited}, Map.put(state, key, hits)}
    else
      {:reply, :ok, Map.put(state, key, [now | hits])}
    end
  end

  @impl true
  def handle_call(:check_and_record_global, _from, state) do
    now  = now_ms()
    hits = Map.get(state, :global, []) |> drop_old(now - @global_window_ms)

    if length(hits) >= @global_limit do
      {:reply, {:error, :rate_limited}, Map.put(state, :global, hits)}
    else
      {:reply, :ok, Map.put(state, :global, [now | hits])}
    end
  end

  # ---------------------------------------------------------------------------
  # Helpers
  # ---------------------------------------------------------------------------

  defp now_ms, do: System.monotonic_time(:millisecond)

  # Drops timestamps older than the window boundary.
  defp drop_old(timestamps, cutoff) do
    Enum.filter(timestamps, fn ts -> ts > cutoff end)
  end
end

