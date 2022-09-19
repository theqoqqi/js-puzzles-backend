<?
/**
 * @author: Qoqqi
 * 19.09.2022, 8:34
 */

namespace App\Http\Controllers;

use App\Http\Middleware\GenerateGuestUuid;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webpatser\Uuid\Uuid;
use function env;

class BaseController extends Controller {

    protected function getUserId(Request $request): string {
        if (env('APP_ENV') === 'local') {
            try {
                return Uuid::generate(5, $request->getClientIp(), Uuid::NS_URL);
            } catch (Exception $e) {
                return $request->getClientIp();
            }
        }

        return $request->cookie(GenerateGuestUuid::COOKIE_NAME);
    }
}
