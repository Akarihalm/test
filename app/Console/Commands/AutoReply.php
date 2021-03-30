<?php

namespace App\Console\Commands;

use App\Enums\ReservationAnswerStatusEnum;
use App\Models\Reservation;
use App\Utils\LineManager;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoReply extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reply:auto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'auto reply';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws GuzzleException
     */
    public function handle()
    {
        Reservation::getList(true)->each(function (Reservation $reservation) {
            $this->answer($reservation);
            dd($reservation->id . ' - ' . $reservation->user->name);
        });

        return 0;
    }

    /**
     * @param Reservation $reservation
     * @throws GuzzleException
     */
    private function answer(Reservation $reservation)
    {
        DB::beginTransaction();

        $statusNumber = $reservation->id % 2 ? 1 : 0;
        $status = strtolower(ReservationAnswerStatusEnum::keys()[$statusNumber]);

        $reservation->update([
            'status' => $status
        ]);

        if ('dispatching' === $status) {
            $title = '配車完了';
            $message = '配車が完了致しました。到着までしばらくお待ち下さい。';
            $type = 'success';
        } else {
            $title = '配車できませんでした';
            $message = '申し訳ございませんが、現在混み合っているため配車できませんでした。お手数おかけしますが時間をおいて再度迎車依頼お願い致します。';
            $type = 'error';
        }

        LineManager::pushMessage($reservation->user->line_id, $message, $title, $type);

        DB::commit();
    }

    /**
     * @return string
     */
    private function getStatusRandom()
    {
        return ReservationAnswerStatusEnum::keys()[round(rand(0, 1))];
    }
}
