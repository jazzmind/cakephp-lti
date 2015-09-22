# An LTI Plugin for CakePHP

The goal of this plugin is to enable a CakePHP application to act as either/both LTI Tool Provider and LTI Tool Consumer. It was designed to provide LTI support to [Practera](http://www.practera.com).

It currently supports basic LTI v1.0, however the plan is to support LTI 2.0 and several other related API standards, eg. Caliper, xAPI, CommonCartridge, etc.

It is a re-write of Stephen Vickers PHP LTI Provider code - done so I could learn exactly how LTI works, but also because Cake allows us to simplify the code substantially and align the data model with Cake's MVC architecture.

## What is LTI

LTI started as a very simple concept - allow a user signed into learning platform A to access some functionality on learning platform B without having to sign in twice - single sign-on for learning tools. When the user clicks on the "launch" button in Platform A, a POST request is sent to platform B with a bunch of info - user name, course details, etc. The entire request is signed by OAuth using a key and secret that are pre-arranged between platform A and B. This means B can verify that the request from A is official, and can then take the user data and either set up its own user account or create a new one.

Enhancements to LTI 1.0 allow platform B to send a "grade" or "outcome" back to Platform A. It also allows Platform A to send more extensive info to Platform B - such as a list of all students enrolled in the course.

LTI 2.0 takes it to the next level, which allows for Platform A and B to talk back and forth around a broad range of data.

## LTI Tool Consumer

A tool consumer is a system that will connect to LTI tools (providers) through "launch" URLs. E.g. Blackboard is a consumer that launches Turnitin as a provider.

The tool consumer should maintain a list of Tool Providers, and their shared OAuth secrets. It should also maintain a list of launch links once they are configured. I should be able to select a tool provider and configure a launch link. I can then get the code for this link and embed it into the app/content as needed.

## LTI Tool Provider

A tool provider is an external learning service that provides some functionality as part of a broader learning program. E.g. Turnitin evaluates student submissions for plagarism.

The tool provider should keep a list of consumers that are authorised to connect to it - including the shared OAuth key and secret. It might also store other configuration information specific to the consumer and check new requests against the configuration for updates.

When a launch request arrives, some parameters might allow the tool provider to communicate back to the tool consumer. E.g. return IDs, which should be saved.


# Writing LTI Stuff

[Source](https://raw.githubusercontent.com/whitmer/edu_apps/master/public/code.html "Permalink to Writing LTI Stuff")

This page goes over the basics of creating an app that leverages LTI. There's a lot of great links at the bottom that may help out, but it's probably a good idea to at least read the intro first to get a feel for what LTI does exactly.

[ruby][1] | [java][2] | [php][2] | [python][3] | [.NET][4]

* Introduction
* POST Parameters
* Building an LTI App
* Other Resources

## Introduction

![][5]

Identity assertion is a one-way "handshake" coming from the learning platform (consumer) to the app (provider).

The main thing with LTI is the identity assertion. LTI is a way for one system (the tool consumer, typically an LMS) to send a user to another system (the tool provider, some service that integrates with the LMS -- sorry, I know the names are confusing. I've tried using colors to help make things clearer, but that may just make it worse...) in a trusted way. The most common reason for the trust assertion is to allow the user to be automatically signed in and directed to a specific course or module when the provider renders content.

![][6]

Tools (providers) are launched from within the learning platform (consumer) in an iframe so they feel like a native part of the platform.

The consumer and provider have some predefined relationship via a consumer key and shared secret that are used to sign any messages passed between systems. All messages are [signed with an OAuth signature][7] that can be verified by either party. For the most part information only travels one way, from the consumer to the provider.

The identity assertion happens through an HTTP POST request from the consumer to the provider. The POST request must happen in the user's browser, which means it needs to be launched by submitting a form. Most of the time the form is submitted via JavaScript to an iframe rendered on a page within the consumer, so the user doesn't have an extra step when trying to launch an app.

Below are a list of parameters that can be sent as part of the POST request. Some are required, some are optional. Most apps shouldn't need more than the first set of parameters and can probably just ignore the rest.

## Building an LTI App

If you want to build an LTI-compliant app or provider then there's really only a couple things you need to worry about: how users can configure your app, how to accept a launch from a consumer, and potentially handling some of the extra goodies LTI makes possible.

### App Configuration

App configuration is [different for every LMS][8] right now, but we're working on that. The best way to provide a standard configuration for your app is by providing a url that returns an xml configuration for your app. There's a lot of [examples of app configurations in the Canvas API documentation][9]. Remember, if there's custom values you want to make sure come across with every user, this is the place to include them. The only really crucial piece to specify is the url endpoint that will accept the POST requests, `blti:launch_url`.

Typically users will either copy the url to your xml configuration, or copy and paste the configuration itself. Notice that the configuration does not include the consumer key or shared secret. These are account-specific values, and if they were included they'd prevent the xml from being reusable. Admins will still have to enter the key and secret values that a provider gives them into the consumer manually.

### App Launch

Once an app is configured, it will be added by one or more instructors into their material as some sort of link or button in the consumer. Any time a student, instructor, administrator, or random internet passersby clicks the link they will be directed to the provider via a signed POST request. It is the provider's responsibility to confirm the signature on the POST request. If the signature is invalid then none of the information should be trusted.

If the signature is valid then you should accept the identity assertion provided by the consumer and log the user in to your service. Many services have their own registration flow, so it's not uncommon to require an additional registration step the first time a user launches your app.

Signatures are generated using [the OAuth signing process][10]. Google provides [a nice tool for generating OAuth signatures][7] that you can use to test your signing code, although you'll probably save yourself some trouble if you can find a library to do the work for you.

### Extras

This page has described the most basic type of LTI integration. There's a number of other things you can do on top of this, including passing scores from the provider back to the gradebook of the consumer, or adding buttons to the rich editor in the consumer to insert rich content generated or curated by the provider. [Check out the extensions demos page][11] or the [Canvas API documentation on external tools][12] for more detail on these extensions and how they work.

## POST Parameters

These are all of the known values that can be passed from the consumer to the provider when a user clicks a link to launch the app.

### Most Common Parameters

| Parameter                  | Status      | Notes                                                                                                                                                                                                                                                                                                                    |
| -------------------------- | ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `lti_message_type`         | required    | set to `basic-lti-launch-request`                                                                                                                                                                                                                                                                                        |
| `lti_version`              | required    | set to `LTI-1p0`                                                                                                                                                                                                                                                                                                         |
| `resource_link_id`         | required    | unique id referencing the link, or "placement", of the app in the consumer. If an app was added twice to the same class, each placement would send a different id, and should be considered a unique "launch". For example, if the provider were a chat room app, then each `resource_link_id` would be a separate room. |
| `user_id`                  | recommended | unique id referencing the user accessing the app. providers should consider this id an opaque identifier.                                                                                                                                                                                                                |
| `user_image`               | optional    | if provided, this would be a url to an avatar image for the current user. We recommend that these urls be 50px wide and tall, and don't expire for at least 3 months.                                                                                                                                                    |
| `roles`                    | recommended | there's a long list of possible roles, but here's the most common ones: |                                                                                                                                                                                                                                                |
|                            |             |  * `Learner`                                                                                                                                                                                                                                                                                                             |
|                            |             |  * `Instructor`                                                                                                                                                                                                                                                                                                          |
|                            |             |  * `ContentDeveloper`                                                                                                                                                                                                                                                                                                    |
|                            |             |  * `urn:lti:instrole:ims/lis/Observer`                                                                                                                                                                                                                                                                                   |
|                            |             |  * `urn:lti:instrole:ims/lis/Administrator`                                                                                                                                                                                                                                                                              |
|  `lis_person_name_full`    | recommended |  Full name of the user accessing the app. This won't be sent if apps are configured to launch users anonymously or with minimal information.                                                                                                                                                                             |
|  `lis_outcome_service_url` | optional    |  If this url is passed to the provider then it means the app is authorized to send grade values back to the consumer gradebook for any students that access the app. There's more information available in the [Canvas API documentation][13].                                                                           |
|  `selection_directive`     | optional    |  If this parameter is passed to the provider then it means the consumer is expecting the provider to return some piece of information such as a url, an html snippet, etc. There's more information available in the [Canvas API documentation][12].                                                                     |

### Additional Parameters

| Parameter                                 | Status      | Notes                                                                                                                                                                                                   |
| ----------------------------------------- | ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `lis_person_name_given`                   | recommended | First name of the user accessing the app. This won't be sent if apps are configured to launch users anonymously or with minimal information.                                                            |
| `lis_person_name_family`                  | recommended | Last name of the user accessing the app. This won't be sent if apps are configured to launch users anonymously or with minimal information.                                                             |
| `lis_person_contact_email_primary`        | recommended | Email address of the user accessing the app. This won't be sent if apps are configured to launch users anonymously or with minimal information.                                                         |
| `resource_link_title`                     | recommended | name of the link that launched the app                                                                                                                                                                  |
| `resource_link_description`               | optional    | description of the link that launched the app                                                                                                                                                           |
| `context_id`                              | recommended | unique id of the course from which the user is accessing the app. If a app were added multiple times to the same course, this id would be the same regardless of which link was used to launch the app. |
| `context_type`                            | optional    | this is the type of context from which the user is accessing the app. If it's provided, this will most likely be `CourseSection`                                                                        |
| `context_title`                           | recommended | name of the course from which the user is accessing the app                                                                                                                                             |
| `context_label`                           | recommended | short name or course code of the course from which the user is accessing the app                                                                                                                        |
| `launch_presentation_locale`              | recommended | locale (i.e. `en-US`) for the user accessing the app                                                                                                                                                    |
| `launch_presentation_document_target`     | recommended | if provided, this value will be either  (if the app has been launched in a new window) or `iframe`.                                                                                                     |
| `launch_presentation_css_url`             | optional    | css file that could be included by the provider to help its styling better match the consumer styling. This isn't well-documented, so I typically pretend it doesn't exist.                             |
| `launch_presentation_width`               | recommended | width of the frame or window in which the app is launched. This is only a starting point, since the frame could change if the user resizes their window.                                                |
| `launch_presentation_height`              | recommended | height of the frame or window in which the app is launched. This is only a starting point, since the frame could change if the user resizes their window.                                               |
| `launch_presentation_return_url`          | recommended | url to send the user to when they're finished with the provider. The provider can optionally send one of four values as query parameters appended to the url:                                           |
|                                           |             |  * `lti_errormsg` \- error message to show to the user                                          |
|                                           |             |  * `lti_errorlog` \- error message for the consumer to optionally store without showing the user                                          |
|                                           |             |  * `lti_msg` \- success message to show to the user                                          |
|                                           |             |  * `lti_log` \- success message for the consumer to optionally store without showing the user                                   |
|  `tool_consumer_info_product_family_code` | recommended |  product name for the consumer. This could be something like `moodle`, `sakai` or `canvas` |
|  `tool_consumer_info_version`             | recommended |  version number of the consumer product. |
|  `tool_consumer_instance_guid`            | strongly recommended |  unique id referencing the instance from which the user is accessing the app. This mostly makes sense only in a multi-tenant environment. |
|  `tool_consumer_instance_name`            | recommended |  name of the instance from which the user is accessing the app. |
|  `tool_consumer_instance_description`     | optional    |  description of the instance from which the user is accessing the app. |
|  `tool_consumer_instance_url`             | optional    |  url of the instance from which the user is accessing the app. |
|  `tool_consumer_instance_contact_email`   | recommended |  email address of a contact at the instance from which the user is accessing the app. |
|  `custom_*`                               | optional    |  any number of custom values can optionally be sent across. The key for any custom values should start with `custom_` and should be in snake case. Custom values can optionally be defined on the consumer side as part of the app configuration process.  |

## Other Resources

### Code / Libraries
* [The list of apps on this site's home page is available as a **jsonp** list][14]
* [Source code for LTI **ruby** gem][1]
* [Source code for LTI **java** and **php** libraries][2]
* [How to use the **python** oauth libraries with LTI, with a link to an example implementation][3]
* [LTI samples written for .NET][4]

### Documentation
* [Examples of apps that leverage LTI extensions][15]
* [Canvas documentation on LTI external tools][12]
* [Blackboard presentation on LTI (pdf - 3.6MB)][16]
* [Official LTI documentation][17]

[1]: https://github.com/instructure/ims-lti
[2]: http://code.google.com/p/ims-dev/
[3]: http://swl10.blogspot.com/2011/03/oauth-python-and-basic-lti.html
[4]: https://ltisamples.codeplex.com/
[5]: https://raw.githubusercontent.com/coding/tool_launch.png
[6]: https://raw.githubusercontent.com/coding/tool_iframe.png
[7]: http://oauth.googlecode.com/svn/code/javascript/example/signature.html
[8]: /tutorials.html
[9]: https://canvas.instructure.com/doc/api/tools_xml.html
[10]: http://oauth.net/core/1.0/#signing_process
[11]: /extensions
[12]: https://canvas.instructure.com/doc/api/tools_intro.html
[13]: https://canvas.instructure.com/doc/api/assignment_tools.html
[14]: /data/lti_examples.jsonp
[15]: /extensions/index.html
[16]: http://www.edugarage.com/download/attachments/72811512/Code+Your+Own+Tool+Integration+Using+the+Basic+Learning+Tools+Interoperability+(LTI)+Standard.pdf?version=1&amp;modificationDate=1311710692377&amp;ei=_3d2T_XuN7H9iQKQyei9DA&amp;usg=AFQjCNF9taxr5y951oY-s0GKcLlFecpk1A&amp;sig2=TLVyrX-TUU5fADlHikNJVQ
[17]: http://www.imsglobal.org/lti/

