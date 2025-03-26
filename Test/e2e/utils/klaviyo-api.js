const axios = require('axios');

const klaviyoPrivateKey = process.env.KLAVIYO_PRIVATE_KEY;
const klaviyoV3Url = process.env.KLAVIYO_V3_URL;

function getHeaders() {
    return {
        'Authorization': `Klaviyo-API-Key ${klaviyoPrivateKey}`,
        'Content-Type': 'application/json',
        'revision': '2025-01-15'
    }
}

/**
 * Creates a profile in Klaviyo
 * @param {string} email - Email address for the profile
 * @returns {Promise<string>} The created profile ID
 */
async function createProfileInKlaviyo(email) {
    const response = await axios.post(`https://${klaviyoV3Url}/profiles/`, {
        data: {
            type: 'profile',
            attributes: {
                email: email
            }
        }
    }, {
        headers: getHeaders(klaviyoPrivateKey)
    });
    return response.data.data.id;
}

/**
 * Checks for events in Klaviyo matching the given profile ID and metric ID
 * @param {string} profileId - Klaviyo profile ID
 * @param {string} metricId - Klaviyo metric ID for the event type
 * @returns {Promise<Array>} Array of matching events
 */
async function checkEvent(profileId, metricId) {
    const response = await axios.get(`https://${klaviyoV3Url}/events/`, {
        headers: getHeaders(klaviyoPrivateKey),
        params: {
            filter: `and(equals(profile_id,"${profileId}"),equals(metric_id,"${metricId}"))`,
            'fields[event]': 'event_properties,datetime',
            'fields[metric]':'name,integration',
            'include':'metric',
            'sort': '-datetime',
        }
    });
    return response.data;
}

/**
 * Checks if a profile exists in Klaviyo and returns its data
 * @param {string} email - Email address to search for
 * @returns {Promise<Array>} Array of profile data
 */
async function checkProfileInKlaviyo(email) {
    const response = await axios.get(`https://${klaviyoV3Url}/profiles/`, {
        headers: getHeaders(klaviyoPrivateKey),
        params: {
            filter: `equals(email,"${email}")`, 'additional-fields[profile]': 'subscriptions',
            'fields[profile]': 'id,email,subscriptions.email.marketing'
        }
    });
    return response.data.data;
}

/**
 * Checks a profile's list relationships in Klaviyo
 * @param {string} profileId - Klaviyo profile ID
 * @returns {Promise<Array>} Array of list relationships
 */
async function checkProfileListRelationships(profileId) {
    const response = await axios.get(`https://${klaviyoV3Url}/profiles/${profileId}/lists`, {
        headers: getHeaders(klaviyoPrivateKey),
        params: {}
    });
    return response.data.data;
}

module.exports = {
    createProfileInKlaviyo,
    checkEvent,
    checkProfileInKlaviyo,
    checkProfileListRelationships
};