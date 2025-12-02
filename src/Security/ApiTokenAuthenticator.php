<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function supports(Request $request): ?bool
{
    $hasAuth = $request->headers->has('Authorization');
    $startsWithBearer = $hasAuth && str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    
    // Debug temporaire
    file_put_contents('/tmp/api_debug.log', sprintf(
        "Path: %s, HasAuth: %s, StartsWithBearer: %s, Header: %s\n",
        $request->getPathInfo(),
        $hasAuth ? 'yes' : 'no',
        $startsWithBearer ? 'yes' : 'no',
        $request->headers->get('Authorization', 'none')
    ), FILE_APPEND);
    
    return $startsWithBearer;
}

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');
        $token = substr($authHeader, 7); // Enlever "Bearer "

        if (!$token) {
            throw new CustomUserMessageAuthenticationException('Token manquant');
        }

        return new SelfValidatingPassport(
            new UserBadge($token, function ($token) {
                $user = $this->userRepository->findOneBy(['apiToken' => $token]);

                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Token invalide');
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // Continuer la requête
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => $exception->getMessage()
        ], Response::HTTP_UNAUTHORIZED);
    }
}