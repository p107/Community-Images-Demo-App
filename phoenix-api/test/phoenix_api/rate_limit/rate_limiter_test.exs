defmodule PhoenixApi.RateLimit.RateLimiterTest do
  use ExUnit.Case, async: true

  alias PhoenixApi.RateLimit.RateLimiter

  # ---------------------------------------------------------------------------
  # Each test starts its own isolated, anonymously-named GenServer so tests
  # run independently and in parallel without sharing state.
  # ---------------------------------------------------------------------------

  defp start_limiter do
    # `name: nil` means no registration — we use the pid directly.
    # We override the module name via `GenServer.call(pid, ...)` calls.
    {:ok, pid} = GenServer.start_link(RateLimiter, %{})
    pid
  end

  # Directly inject pre-aged timestamps into the GenServer state so we can
  # simulate the sliding window expiring without sleeping.
  defp inject_old_hits(pid, key, count, age_ms) do
    old_ts = System.monotonic_time(:millisecond) - age_ms

    :sys.replace_state(pid, fn state ->
      Map.put(state, key, List.duplicate(old_ts, count))
    end)
  end

  # Convenience wrappers that call the given pid instead of the named process.
  defp check_user(pid, user_id),
    do: GenServer.call(pid, {:check_and_record_user, user_id})

  defp check_global(pid),
    do: GenServer.call(pid, :check_and_record_global)

  # ---------------------------------------------------------------------------
  # Per-user limit: 5 / 10 min
  # ---------------------------------------------------------------------------

  test "allows first 5 requests for a user and rejects the 6th" do
    pid = start_limiter()

    for _ <- 1..5 do
      assert :ok = check_user(pid, "user_1")
    end

    assert {:error, :rate_limited} = check_user(pid, "user_1")
  end

  test "limits are tracked per user independently" do
    pid = start_limiter()

    for _ <- 1..5 do
      assert :ok = check_user(pid, "user_a")
    end

    # user_b has a clean slate
    assert :ok = check_user(pid, "user_b")
  end

  test "rejects request when user window is full but allows after window expires" do
    pid = start_limiter()

    # Fill the user's limit
    for _ <- 1..5, do: check_user(pid, "user_2")
    assert {:error, :rate_limited} = check_user(pid, "user_2")

    # Simulate all hits ageing out (inject timestamps older than 10 min)
    inject_old_hits(pid, {:user, "user_2"}, 5, 10 * 60 * 1_000 + 1)

    # Window has expired — request should be allowed again
    assert :ok = check_user(pid, "user_2")
  end

  test "only hits inside the window count toward the limit" do
    pid = start_limiter()

    # Inject 4 old hits (outside window) + make 1 fresh hit
    inject_old_hits(pid, {:user, "user_3"}, 4, 10 * 60 * 1_000 + 1)
    assert :ok = check_user(pid, "user_3")

    # Only 1 fresh hit in the window — 4 more are still allowed
    for _ <- 1..4, do: assert(:ok = check_user(pid, "user_3"))

    # Now at 5 fresh hits → 6th is rejected
    assert {:error, :rate_limited} = check_user(pid, "user_3")
  end

  # ---------------------------------------------------------------------------
  # Global limit: 1000 / hour
  # ---------------------------------------------------------------------------

  test "allows exactly 1000 global requests and rejects the 1001st" do
    pid = start_limiter()

    for _ <- 1..1_000 do
      assert :ok = check_global(pid)
    end

    assert {:error, :rate_limited} = check_global(pid)
  end

  test "global limit resets after the hour window expires" do
    pid = start_limiter()

    # Fill global limit
    for _ <- 1..1_000, do: check_global(pid)
    assert {:error, :rate_limited} = check_global(pid)

    # Age out all hits
    inject_old_hits(pid, :global, 1_000, 60 * 60 * 1_000 + 1)

    assert :ok = check_global(pid)
  end

  test "user limit and global limit are checked independently" do
    pid = start_limiter()

    # User exhausts their limit
    for _ <- 1..5, do: check_user(pid, "user_x")
    assert {:error, :rate_limited} = check_user(pid, "user_x")

    # Global limit is unaffected — a different user can still import
    assert :ok = check_user(pid, "user_y")
    assert :ok = check_global(pid)
  end
end

