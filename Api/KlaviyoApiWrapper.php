use Configuration;
use KlaviyoV3Sdk\Exception\KlaviyoApiException;
use KlaviyoV3Sdk\Klaviyo;
use KlaviyoV3Sdk\KlaviyoV3Api;
use KlaviyoV3Sdk\Exception;

class KlaviyoApiWrapper
{
    /** @var Klaviyo Client for Klaviyo's Api. */
    protected $client;

    public function __construct()
    {
        $this->client = new KlaviyoV3Api(Configuration::get('KLAVIYO_PRIVATE_API'), Configuration::get('KLAVIYO_PUBLIC_API'));
    }

    /**
     * Get all lists for specific Klaviyo account.
     *
     * @return mixed
     */
    public function getLists()
    {
        return $this->client->getLists();
    }

    /**
     * Subscribe email to the Subscriber List selected on configuration page (if selected).
     *
     * @param string $email
     * @throws KlaviyoApiException
     */
    public function subscribeCustomer($email, $customProperties = [])
    {
        $profile = array(
            'type' => 'profile',
            'attributes' => array(
                'email' => $email,
                'subscriptions' => array(
                    'email' => [
                        'MARKETING'
                    ]
                )
            )
        );

        $listId = Configuration::get('KLAVIYO_SUBSCRIBER_LIST');

        if ($listId) {
            $this->client->subscribeMembersToList($listId, array($profile));
        }
    }

    /**
     * Send event to Klaviyo using the Track endpoint.
     *
     * @param array $event
     * @return bool
     * @throws KlaviyoApiException
     */
    public function trackEvent(array $eventConfig)
    {
        return (bool) $this->client->track($eventConfig);
    }
}